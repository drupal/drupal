<?php
// $Id: search.extender.inc,v 1.3 2010/01/13 23:19:54 dries Exp $

/**
 * @file
 * Search query extender and helper functions.
 */

/**
 * Do a query on the full-text search index for a word or words.
 *
 * This function is normally only called by each module that supports the
 * indexed search (and thus, implements hook_update_index()).
 *
 * Results are retrieved in two logical passes. However, the two passes are
 * joined together into a single query. And in the case of most simple
 * queries the second pass is not even used.
 *
 * The first pass selects a set of all possible matches, which has the benefit
 * of also providing the exact result set for simple "AND" or "OR" searches.
 *
 * The second portion of the query further refines this set by verifying
 * advanced text conditions (such as negative or phrase matches).
 *
 * The used query object has the tag 'search_$type' and can be further extended
 * with hook_query_alter().
 */
class SearchQuery extends SelectQueryExtender {
  /**
   * The search query that is used for searching.
   *
   * @var string
   */
  protected $searchExpression;

  /**
   * Type of search.
   *
   * This maps to the value of the type column in search_index.
   *
   * @var string
   */
  protected $type;

  /**
   * Positive and negative search keys.
   *
   * @var array
   */
  protected $keys = array('positive' => array(), 'negative' => array());

  /**
   * Indicates if the first pass query requires complex conditions (LIKE).
   *
   * @var boolean.
   */
  protected $simple = TRUE;

  /**
   * Conditions that are used for exact searches.
   *
   * This is always used for the second pass query but not for the first pass,
   * unless $this->simple is FALSE.
   *
   * @var DatabaseCondition
   */
  protected $conditions;

  /**
   * Indicates how many matches for a search query are necessary.
   *
   * @var int
   */
  protected $matches = 0;

  /**
   * Array of search words.
   *
   * These words have to match against {search_index}.word.
   *
   * @var array
   */
  protected $words = array();

  /**
   * Multiplier for the normalized search score.
   *
   * This value is calculated by the first pass query and multiplied with the
   * actual score of a specific word to make sure that the resulting calculated
   * score is between 0 and 1.
   *
   * @var float
   */
  protected $normalize;

  /**
   * Indicates if the first pass query has been executed.
   *
   * @var boolean
   */
  protected $executedFirstPass = FALSE;

  /**
   * Stores score expressions.
   *
   * @var array
   */
  protected $scores = array();

  /**
   * Stores arguments for score expressions.
   *
   * @var array
   */
  protected $scoresArguments = array();

  /**
   * Total value of all the multipliers.
   *
   * @var array
   */
  protected $multiply = array();

  /**
   * Search items for the given search query string and type.
   *
   * @param $query
   *   A search query string, that can contain options.
   * @param $type
   *   The type of search, this maps to {search_index}.type.
   * @return
   *   The SearchQuery object.
   */
  public function searchExpression($expression, $type) {
    $this->searchExpression = $expression;
    $this->type = $type;

    return $this;
  }

  /**
   * Apply a search option and remove it from the search query string.
   *
   * These options are in the form option:value,value2,value3.
   *
   * @param $option
   *   Name of the option.
   * @param $column
   *   Name of the db column to which the value should be applied.
   * @return
   *   TRUE if at least a value for that option has been found, FALSE if not.
   */
  public function setOption($option, $column) {
    if ($values = search_expression_extract($this->searchExpression, $option)) {
      $or = db_or();
      foreach (explode(',', $values) as $value) {
        $or->condition($column, $value);
      }
      $this->condition($or);
      $this->searchExpression = search_expression_insert($this->searchExpression, $option);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Parse a search query into SQL conditions.
   *
   * We build two queries that matches the dataset bodies.
   */
  protected function parseSearchExpression() {
    // Matchs words optionally prefixed by a dash. A word in this case is
    // something between two spaces, optionally quoted.
    preg_match_all('/ (-?)("[^"]+"|[^" ]+)/i', ' ' .  $this->searchExpression , $keywords, PREG_SET_ORDER);

    if (count($keywords) ==  0) {
      return;
    }

    // Classify tokens.
    $or = FALSE;
    $warning = '';
    foreach ($keywords as $match) {
      $phrase = FALSE;
      // Strip off phrase quotes.
      if ($match[2]{0} == '"') {
        $match[2] = substr($match[2], 1, -1);
        $phrase = TRUE;
        $this->simple = FALSE;
      }
      // Simplify keyword according to indexing rules and external preprocessors.
      $words = search_simplify($match[2]);
      // Re-explode in case simplification added more words, except when
      // matching a phrase.
      $words = $phrase ? array($words) : preg_split('/ /', $words, -1, PREG_SPLIT_NO_EMPTY);
      // Negative matches.
      if ($match[1] == '-') {
        $this->keys['negative'] = array_merge($this->keys['negative'], $words);
      }
      // OR operator: instead of a single keyword, we store an array of all
      // OR'd keywords.
      elseif ($match[2] == 'OR' && count($this->keys['positive'])) {
        $last = array_pop($this->keys['positive']);
        // Starting a new OR?
        if (!is_array($last)) {
          $last = array($last);
        }
        $this->keys['positive'][] = $last;
        $or = TRUE;
        continue;
      }
      // AND operator: implied, so just ignore it.
      elseif ($match[2] == 'AND' || $match[2] == 'and') {
        $warning = $match[2];
        continue;
      }

      // Plain keyword.
      else {
        if ($match[2] == 'or') {
          $warning = $match[2];
        }
        if ($or) {
          // Add to last element (which is an array).
          $this->keys['positive'][count($this->keys['positive']) - 1] = array_merge($this->keys['positive'][count($this->keys['positive']) - 1], $words);
        }
        else {
          $this->keys['positive'] = array_merge($this->keys['positive'], $words);
        }
      }
      $or = FALSE;
    }

    // Convert keywords into SQL statements.
    $this->conditions = db_and();
    $simple_and = FALSE;
    $simple_or = FALSE;
    // Positive matches.
    foreach ($this->keys['positive'] as $key) {
      // Group of ORed terms.
      if (is_array($key) && count($key)) {
        $simple_or = TRUE;
        $any = FALSE;
        $queryor = db_or();
        foreach ($key as $or) {
          list($num_new_scores) = $this->parseWord($or);
          $any |= $num_new_scores;
          $queryor->condition('d.data', "% $or %", 'LIKE');
        }
        if (count($queryor)) {
          $this->conditions->condition($queryor);
          // A group of OR keywords only needs to match once.
          $this->matches += ($any > 0);
        }
      }
      // Single ANDed term.
      else {
        $simple_and = TRUE;
        list($num_new_scores, $num_valid_words) = $this->parseWord($key);
        $this->conditions->condition('d.data', "% $key %", 'LIKE');
        if (!$num_valid_words) {
          $this->simple = FALSE;
        }
        // Each AND keyword needs to match at least once.
        $this->matches += $num_new_scores;
      }
    }
    if ($simple_and && $simple_or) {
      $this->simple = FALSE;
    }
    // Negative matches.
    foreach ($this->keys['negative'] as $key) {
      $this->conditions->condition('d.data', "% $key %", 'NOT LIKE');
      $this->simple = FALSE;
    }

    if ($warning == 'or') {
      drupal_set_message(t('Search for either of the two terms with uppercase <strong>OR</strong>. For example, <strong>cats OR dogs</strong>.'));
    }
  }

  /**
   * Helper function for parseQuery().
   */
  protected function parseWord($word) {
    $num_new_scores = 0;
    $num_valid_words = 0;
    // Determine the scorewords of this word/phrase.
    $split = explode(' ', $word);
    foreach ($split as $s) {
      $num = is_numeric($s);
      if ($num || drupal_strlen($s) >= variable_get('minimum_word_size', 3)) {
        $s = $num ? ((int)ltrim($s, '-0')) : $s;
        if (!isset($this->words[$s])) {
          $this->words[$s] = $s;
          $num_new_scores++;
        }
        $num_valid_words++;
      }
    }
    // Return matching snippet and number of added words.
    return array($num_new_scores, $num_valid_words);
  }

  /**
   * Execute the first pass query.
   *
   * This can either be done explicitly, so that additional scores and
   * conditions can be applied to the second pass query or implicitly by
   * addScore() or execute().
   *
   * @return
   *   TRUE if search items exist, FALSE if not.
   */
  public function executeFirstPass() {
    $this->parseSearchExpression();

    if (count($this->words) == 0) {
      form_set_error('keys', format_plural(variable_get('minimum_word_size', 3), 'You must include at least one positive keyword with 1 character or more.', 'You must include at least one positive keyword with @count characters or more.'));
      return FALSE;
    }
    $this->executedFirstPass = TRUE;

    if (!empty($this->words)) {
      $or = db_or();
      foreach ($this->words as $word) {
        $or->condition('i.word', $word);
      }
      $this->condition($or);
    }
    // Build query for keyword normalization.
    $this->join('search_total', 't', 'i.word = t.word');
    $this
      ->condition('i.type', $this->type)
      ->groupBy('i.type')
      ->groupBy('i.sid')
      ->having('COUNT(*) >= :matches', array(':matches' => $this->matches));

    // Clone the query object to do the firstPass query;
    $first = clone $this->query;

    // For complex search queries, add the LIKE conditions to the first pass query.
    if (!$this->simple) {
      $first->join('search_dataset', 'd', 'i.sid = d.sid AND i.type = d.type');
      $first->condition($this->conditions);
    }

    // Calculate maximum keyword relevance, to normalize it.
    $first->addExpression('SUM(i.score * t.count)', 'calculated_score');
    $this->normalize = $first
      ->range(0, 1)
      ->orderBy('calculated_score', 'DESC')
      ->execute()
      ->fetchField();

    if ($this->normalize) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds a custom score expression to the search query.
   *
   * Each score expression can optionally use a multiplicator and multiple
   * expressions are combined.
   *
   * @param $score
   *   The score expression.
   * @param $arguments
   *   Custom query arguments for that expression.
   * @param $multiply
   *   If set, the score is multiplied with that value. Search query ensures
   *   that the search scores are still normalized.
   */
  public function addScore($score, $arguments = array(), $multiply = FALSE) {
    if ($multiply) {
      $i = count($this->multiply);
      $score = "CAST(:multiply_$i AS DECIMAL) * COALESCE(( " . $score . "), 0) / CAST(:total_$i AS DECIMAL)";
      $arguments[':multiply_' . $i] = $multiply;
      $this->multiply[] = $multiply;
    }

    $this->scores[] = $score;
    $this->scoresArguments += $arguments;

    return $this;
  }

  /**
   * Execute the search.
   *
   * If not already done, this executes the first pass query, then the complex
   * conditions are applied to the query including score expressions and
   * ordering.
   *
   * @return
   *   FALSE if the first pass query returned no results and a database result
   *   set if not.
   */
  public function execute()
  {
    if (!$this->executedFirstPass) {
      $this->executeFirstPass();
    }
    if (!$this->normalize) {
      return new DatabaseStatementEmpty();
    }

    $this->join('search_dataset', 'd', 'i.sid = d.sid AND i.type = d.type');
    $this->condition($this->conditions);

    if (empty($this->scores)) {
      // Add default score.
      $this->addScore('i.relevance');
    }
    if (count($this->getOrderBy()) == 0) {
      // Add default order.
      $this->orderBy('calculated_score', 'DESC');
    }

    if (count($this->multiply)) {
      // Add the total multiplicator as many times as requested to maintain
      // normalization as far as possible.
      $i = 0;
      $sum = array_sum($this->multiply);
      foreach ($this->multiply as $total) {
        $this->scoresArguments['total_' . $i] = $sum;
      }
    }

    // Replace i.relevance pseudo-field with the actual, normalized value.
    $this->scores = str_replace('i.relevance', '(' . (1.0 / $this->normalize) . ' * i.score * t.count)', $this->scores);
    // Convert scores to an expression.
    $this->addExpression('SUM(' . implode(' + ', $this->scores) . ')', 'calculated_score', $this->scoresArguments);

    // Add tag and useful metadata.
    $this
      ->addTag('search_' . $this->type)
      ->addMetaData('normalize', $this->normalize)
      ->fields('i', array('type', 'sid'));

    return $this->query->execute();
  }
}
