<?php

/**
 * @file
 * Definition of Drupal\search\SearchQuery.
 *
 * Search query extender and helper functions.
 */

namespace Drupal\search;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementEmpty;

/**
 * Performs a query on the full-text search index for a word or words.
 *
 * This query is used by search plugins that use the search index (not all
 * search plugins do, as some use a different searching mechanism). It
 * assumes you have set up a query on the {search_index} table with alias 'i',
 * and will only work if the user is searching for at least one "positive"
 * keyword or phrase.
 *
 * For efficiency, users of this query can run the prepareAndNormalize()
 * method to figure out if there are any search results, before fully setting
 * up and calling execute() to execute the query. The scoring expressions are
 * not needed until the execute() step. However, it's not really necessary
 * to do this, because this class's execute() method does that anyway.
 *
 * During both the prepareAndNormalize() and execute() steps, there can be
 * problems. Call getStatus() to figure out if the query is OK or not.
 *
 * The query object is given the tag 'search_$type' and can be further
 * extended with hook_query_alter().
 */
class SearchQuery extends SelectExtender {

  /**
   * Indicates no positive keywords were in the search expression.
   *
   * Positive keywords are words that are searched for, as opposed to negative
   * keywords, which are words that are excluded. To count as a keyword, a
   * word must be at least
   * \Drupal::config('search.settings')->get('index.minimum_word_size')
   * characters.
   *
   * @see SearchQuery::getStatus()
   */
  const NO_POSITIVE_KEYWORDS = 1;

  /**
   * Indicates that part of the search expression was ignored.
   *
   * To prevent Denial of Service attacks, only
   * \Drupal::config('search.settings')->get('and_or_limit') expressions
   * (positive keywords, phrases, negative keywords) are allowed; this flag
   * indicates that expressions existed past that limit and they were removed.
   *
   * @see SearchQuery::getStatus()
   */
  const EXPRESSIONS_IGNORED = 2;

  /**
   * Indicates that lower-case "or" was in the search expression.
   *
   * The word "or" in lower case was found in the search expression. This
   * probably means someone was trying to do an OR search but used lower-case
   * instead of upper-case.
   *
   * @see SearchQuery::getStatus()
   */
  const LOWER_CASE_OR = 4;

  /**
   * Indicates that no positive keyword matches were found.
   *
   * @see SearchQuery::getStatus()
   */
  const NO_KEYWORD_MATCHES = 8;

  /**
   * The keywords and advanced search options that are entered by the user.
   *
   * @var string
   */
  protected $searchExpression;

  /**
   * The type of search (search type).
   *
   * This maps to the value of the type column in search_index, and is usually
   * equal to the machine-readable name of the plugin or the search page.
   *
   * @var string
   */
  protected $type;

  /**
   * Parsed-out positive and negative search keys.
   *
   * @var array
   */
  protected $keys = array('positive' => array(), 'negative' => array());

  /**
   * Indicates whether the query conditions are simple or complex (LIKE).
   *
   * @var bool
   */
  protected $simple = TRUE;

  /**
   * Conditions that are used for exact searches.
   *
   * This is always used for the second step in the query, but is not part of
   * the preparation step unless $this->simple is FALSE.
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
   * Array of positive search words.
   *
   * These words have to match against {search_index}.word.
   *
   * @var array
   */
  protected $words = array();

  /**
   * Multiplier to normalize the keyword score.
   *
   * This value is calculated by the preparation step, and is used as a
   * multiplier of the word scores to make sure they are between 0 and 1.
   *
   * @var float
   */
  protected $normalize = 0;

  /**
   * Indicates whether the preparation step has been executed.
   *
   * @var bool
   */
  protected $executedPrepare = FALSE;

  /**
   * A bitmap of status conditions, described in getStatus().
   *
   * @var int
   *
   * @see SearchQuery::getStatus()
   */
  protected $status = 0;

  /**
   * The word score expressions.
   *
   * @var array
   *
   * @see SearchQuery::addScore()
   */
  protected $scores = array();

  /**
   * Arguments for the score expressions.
   *
   * @var array
   */
  protected $scoresArguments = array();

  /**
   * The number of 'i.relevance' occurrences in score expressions.
   *
   * @var int
   */
  protected $relevance_count = 0;

  /**
   * Multipliers for score expressions.
   *
   * @var array
   */
  protected $multiply = array();

  /**
   * Sets the search query expression.
   *
   * @param string $expression
   *   A search string, which can contain keywords and options.
   * @param string $type
   *   The search type. This maps to {search_index}.type in the database.
   *
   * @return $this
   */
  public function searchExpression($expression, $type) {
    $this->searchExpression = $expression;
    $this->type = $type;

    // Add query tag.
    $this->addTag('search_' . $type);

    // Initialize conditions and status.
    $this->conditions = db_and();
    $this->status = 0;

    return $this;
  }

  /**
   * Parses the search query into SQL conditions.
   *
   * Sets up the following variables:
   * - $this->keys
   * - $this->words
   * - $this->conditions
   * - $this->simple
   * - $this->matches
   */
  protected function parseSearchExpression() {
    // Matches words optionally prefixed by a - sign. A word in this case is
    // something between two spaces, optionally quoted.
    preg_match_all('/ (-?)("[^"]+"|[^" ]+)/i', ' ' .  $this->searchExpression , $keywords, PREG_SET_ORDER);

    if (count($keywords) ==  0) {
      return;
    }

    // Classify tokens.
    $in_or = FALSE;
    $limit_combinations = \Drupal::config('search.settings')->get('and_or_limit');
    // The first search expression does not count as AND.
    $and_count = -1;
    $or_count = 0;
    foreach ($keywords as $match) {
      if ($or_count && $and_count + $or_count >= $limit_combinations) {
        // Ignore all further search expressions to prevent Denial-of-Service
        // attacks using a high number of AND/OR combinations.
        $this->status |= SearchQuery::EXPRESSIONS_IGNORED;
        break;
      }

      // Strip off phrase quotes.
      $phrase = FALSE;
      if ($match[2]{0} == '"') {
        $match[2] = substr($match[2], 1, -1);
        $phrase = TRUE;
        $this->simple = FALSE;
      }

      // Simplify keyword according to indexing rules and external
      // preprocessors. Use same process as during search indexing, so it
      // will match search index.
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
        $in_or = TRUE;
        $or_count++;
        continue;
      }
      // AND operator: implied, so just ignore it.
      elseif ($match[2] == 'AND' || $match[2] == 'and') {
        continue;
      }

      // Plain keyword.
      else {
        if ($match[2] == 'or') {
          // Lower-case "or" instead of "OR" is a warning condition.
          $this->status |= SearchQuery::LOWER_CASE_OR;
        }
        if ($in_or) {
          // Add to last element (which is an array).
          $this->keys['positive'][count($this->keys['positive']) - 1] = array_merge($this->keys['positive'][count($this->keys['positive']) - 1], $words);
        }
        else {
          $this->keys['positive'] = array_merge($this->keys['positive'], $words);
          $and_count++;
        }
      }
      $in_or = FALSE;
    }

    // Convert keywords into SQL statements.
    $has_and = FALSE;
    $has_or = FALSE;
    // Positive matches.
    foreach ($this->keys['positive'] as $key) {
      // Group of ORed terms.
      if (is_array($key) && count($key)) {
        // If we had already found one OR, this is another one AND-ed with the
        // first, meaning it is not a simple query.
        if ($has_or) {
          $this->simple = FALSE;
        }
        $has_or = TRUE;
        $has_new_scores = FALSE;
        $queryor = db_or();
        foreach ($key as $or) {
          list($num_new_scores) = $this->parseWord($or);
          $has_new_scores |= $num_new_scores;
          $queryor->condition('d.data', "% $or %", 'LIKE');
        }
        if (count($queryor)) {
          $this->conditions->condition($queryor);
          // A group of OR keywords only needs to match once.
          $this->matches += ($has_new_scores > 0);
        }
      }
      // Single ANDed term.
      else {
        $has_and = TRUE;
        list($num_new_scores, $num_valid_words) = $this->parseWord($key);
        $this->conditions->condition('d.data', "% $key %", 'LIKE');
        if (!$num_valid_words) {
          $this->simple = FALSE;
        }
        // Each AND keyword needs to match at least once.
        $this->matches += $num_new_scores;
      }
    }
    if ($has_and && $has_or) {
      $this->simple = FALSE;
    }

    // Negative matches.
    foreach ($this->keys['negative'] as $key) {
      $this->conditions->condition('d.data', "% $key %", 'NOT LIKE');
      $this->simple = FALSE;
    }
  }

  /**
   * Parses a word or phrase for parseQuery().
   *
   * Splits a phrase into words. Adds its words to $this->words, if it is not
   * already there. Returns a list containing the number of new words found,
   * and the total number of words in the phrase.
   */
  protected function parseWord($word) {
    $num_new_scores = 0;
    $num_valid_words = 0;

    // Determine the scorewords of this word/phrase.
    $split = explode(' ', $word);
    foreach ($split as $s) {
      $num = is_numeric($s);
      if ($num || Unicode::strlen($s) >= \Drupal::config('search.settings')->get('index.minimum_word_size')) {
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
   * Prepares the query and calculates the normalization factor.
   *
   * After the query is normalized the keywords are weighted to give the results
   * a relevancy score. The query is ready for execution after this.
   *
   * Error and warning conditions can apply. Call getStatus() after calling
   * this method to retrieve them.
   *
   * @return bool
   *   TRUE if at least one keyword matched the search index; FALSE if not.
   */
  public function prepareAndNormalize() {
    $this->parseSearchExpression();
    $this->executedPrepare = TRUE;

    if (count($this->words) == 0) {
      // Although the query could proceed, there is no point in joining
      // with other tables and attempting to normalize if there are no
      // keywords present.
      $this->status |= SearchQuery::NO_POSITIVE_KEYWORDS;
      return FALSE;
    }

    // Build the basic search query: match the entered keywords.
    $or = db_or();
    foreach ($this->words as $word) {
      $or->condition('i.word', $word);
    }
    $this->condition($or);

    // Add keyword normalization information to the query.
    $this->join('search_total', 't', 'i.word = t.word');
    $this
      ->condition('i.type', $this->type)
      ->groupBy('i.type')
      ->groupBy('i.sid');

    // If the query is simple, we should have calculated the number of
    // matching words we need to find, so impose that criterion. For non-
    // simple queries, this condition could lead to incorrectly deciding not
    // to continue with the full query.
    if ($this->simple) {
      $this->having('COUNT(*) >= :matches', array(':matches' => $this->matches));
    }

    // Clone the query object to calculate normalization.
    $normalize_query = clone $this->query;

    // For complex search queries, add the LIKE conditions; if the query is
    // simple, we do not need them for normalization.
    if (!$this->simple) {
      $normalize_query->join('search_dataset', 'd', 'i.sid = d.sid AND i.type = d.type AND i.langcode = d.langcode');
      if (count($this->conditions)) {
        $normalize_query->condition($this->conditions);
      }
    }

    // Calculate normalization, which is the max of all the search scores for
    // positive keywords in the query. And note that the query could have other
    // fields added to it by the user of this extension.
    $normalize_query->addExpression('SUM(i.score * t.count)', 'calculated_score');
    $result = $normalize_query
      ->range(0, 1)
      ->orderBy('calculated_score', 'DESC')
      ->execute()
      ->fetchObject();
    if (isset($result->calculated_score)) {
      $this->normalize = (float) $result->calculated_score;
    }

    if ($this->normalize) {
      return TRUE;
    }

    // If the normalization value was zero, that indicates there were no
    // matches to the supplied positive keywords.
    $this->status |= SearchQuery::NO_KEYWORD_MATCHES;
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute(SelectInterface $query = NULL) {
    if (!$this->executedPrepare) {
      $this->prepareAndNormalize();
    }

    if (!$this->normalize) {
      return FALSE;
    }

    return parent::preExecute($query);
  }

  /**
   * Adds a custom score expression to the search query.
   *
   * Score expressions are used to order search results. If no calls to
   * addScore() have taken place, a default keyword relevance score will be
   * used. However, if at least one call to addScore() has taken place, the
   * keyword relevance score is not automatically added.
   *
   * Note that you must use this method to add ordering to your searches, and
   * not call orderBy() directly, when using the SearchQuery extender. This is
   * because of the two-pass system the SearchQuery class uses to normalize
   * scores.
   *
   * @param string $score
   *   The score expression, which should evaluate to a number between 0 and 1.
   *   The string 'i.relevance' in a score expression will be replaced by a
   *   measure of keyword relevance between 0 and 1.
   * @param array $arguments
   *   Query arguments needed to provide values to the score expression.
   * @param float $multiply
   *   If set, the score is multiplied with this value. However, all scores
   *   with multipliers are then divided by the total of all multipliers, so
   *   that overall, the normalization is maintained.
   *
   * @return $this
   */
  public function addScore($score, $arguments = array(), $multiply = FALSE) {
    if ($multiply) {
      $i = count($this->multiply);
      // Modify the score expression so it is multiplied by the multiplier,
      // with a divisor to renormalize. Note that the ROUND here is necessary
      // for PostgreSQL and SQLite in order to ensure that the :multiply_* and
      // :total_* arguments are treated as a numeric type, because the
      // PostgreSQL PDO driver sometimes puts values in as strings instead of
      // numbers in complex expressions like this.
      $score = "(ROUND(:multiply_$i, 4)) * COALESCE(($score), 0) / (ROUND(:total_$i, 4))";
      // Add an argument for the multiplier. The :total_$i argument is taken
      // care of in the execute() method, which is when the total divisor is
      // calculated.
      $arguments[':multiply_' . $i] = $multiply;
      $this->multiply[] = $multiply;
    }

    // Search scoring needs a way to include a keyword relevance in the score.
    // For historical reasons, this is done by putting 'i.relevance' into the
    // search expression. So, use string replacement to change this to a
    // calculated query expression, counting the number of occurrences so
    // in the execute() method we can add arguments.
    while (($pos = strpos($score, 'i.relevance')) !== FALSE) {
      $pieces = explode('i.relevance', $score, 2);
      $score = implode('((ROUND(:normalization_' . $this->relevance_count . ', 4)) * i.score * t.count)', $pieces);
      $this->relevance_count++;
    }

    $this->scores[] = $score;
    $this->scoresArguments += $arguments;

    return $this;
  }

  /**
   * Executes the search.
   *
   * The complex conditions are applied to the query including score
   * expressions and ordering.
   *
   * Error and warning conditions can apply. Call getStatus() after calling
   * this method to retrieve them.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   A query result set containing the results of the query.
   */
  public function execute() {
    if (!$this->preExecute($this)) {
      return NULL;
    }

    // Add conditions to the query.
    $this->join('search_dataset', 'd', 'i.sid = d.sid AND i.type = d.type AND i.langcode = d.langcode');
    if (count($this->conditions)) {
      $this->condition($this->conditions);
    }

    // Add default score (keyword relevance) if there are not any defined.
    if (empty($this->scores)) {
      $this->addScore('i.relevance');
    }

    if (count($this->multiply)) {
      // Re-normalize scores with multipliers by dividing by the total of all
      // multipliers. The expressions were altered in addScore(), so here just
      // add the arguments for the total.
      $sum = array_sum($this->multiply);
      for ($i = 0; $i < count($this->multiply); $i++) {
        $this->scoresArguments[':total_' . $i] = $sum;
      }
    }


    // Add arguments for the keyword relevance normalization number.
    $normalization = 1.0 / $this->normalize;
    for ($i = 0; $i < $this->relevance_count; $i++ ) {
      $this->scoresArguments[':normalization_' . $i] = $normalization;
    }

    // Add all scores together to form a query field.
    $this->addExpression('SUM(' . implode(' + ', $this->scores) . ')', 'calculated_score', $this->scoresArguments);

    // If an order has not yet been set for this query, add a default order
    // that sorts by the calculated sum of scores.
    if (count($this->getOrderBy()) == 0) {
      $this->orderBy('calculated_score', 'DESC');
    }

    // Add query metadata.
    $this
      ->addMetaData('normalize', $this->normalize)
      ->fields('i', array('type', 'sid'));
    return $this->query->execute();
  }

  /**
   * Builds the default count query for SearchQuery.
   *
   * Since SearchQuery always uses GROUP BY, we can default to a subquery. We
   * also add the same conditions as execute() because countQuery() is called
   * first.
   */
  public function countQuery() {
    if (!$this->executedPrepare) {
      $this->prepareAndNormalize();
    }

    // Clone the inner query.
    $inner = clone $this->query;

    // Add conditions to query.
    $inner->join('search_dataset', 'd', 'i.sid = d.sid AND i.type = d.type');
    if (count($this->conditions)) {
      $inner->condition($this->conditions);
    }

    // Remove existing fields and expressions, they are not needed for a count
    // query.
    $fields =& $inner->getFields();
    $fields = array();
    $expressions =& $inner->getExpressions();
    $expressions = array();

    // Add sid as the only field and count them as a subquery.
    $count = db_select($inner->fields('i', array('sid')), NULL, array('target' => 'replica'));

    // Add the COUNT() expression.
    $count->addExpression('COUNT(*)');

    return $count;
  }

  /**
   * Returns the query status bitmap.
   *
   * @return int
   *   A bitmap indicating query status. Zero indicates there were no problems.
   *   A non-zero value is a combination of one or more of the following flags:
   *   - SearchQuery::NO_POSITIVE_KEYWORDS
   *   - SearchQuery::EXPRESSIONS_IGNORED
   *   - SearchQuery::LOWER_CASE_OR
   *   - SearchQuery::NO_KEYWORD_MATCHES
   */
  public function getStatus() {
    return $this->status;
  }

}
