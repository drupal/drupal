<?php

namespace Drupal\search\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search\ViewsSearchQuery;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filter handler for search keywords.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_keywords")
 */
class Search extends FilterPluginBase {

  /**
   * This filter is always considered multiple-valued.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * A search query to use for parsing search keywords.
   *
   * @var \Drupal\search\ViewsSearchQuery
   */
  protected $searchQuery = NULL;

  /**
   * TRUE if the search query has been parsed.
   *
   * @var bool
   */
  protected $parsed = FALSE;

  /**
   * The search type name (value of {search_index}.type in the database).
   *
   * @var string
   */
  protected $searchType;

  /**
   * The search score.
   */
  public string $search_score;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->searchType = $this->definition['search_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'optional';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    $form['operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('On empty input'),
      '#default_value' => $this->operator,
      '#options' => [
        'optional' => $this->t('Show All'),
        'required' => $this->t('Show None'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => $this->value,
      '#attributes' => ['title' => $this->t('Search keywords')],
      '#title' => !$form_state->get('exposed') ? $this->t('Keywords') : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (!isset($this->options['expose']['identifier'])) {
      return;
    }

    $key = $this->options['expose']['identifier'];
    if (!$form_state->isValueEmpty($key)) {
      $this->queryParseSearchExpression($form_state->getValue($key));
      if (count($this->searchQuery->words()) == 0) {
        $form_state->setErrorByName($key, $this->formatPlural(\Drupal::config('search.settings')->get('index.minimum_word_size'), 'You must include at least one keyword to match in the content, and punctuation is ignored.', 'You must include at least one keyword to match in the content. Keywords must be at least @count characters, and punctuation is ignored.'));
      }
    }
  }

  /**
   * Sets up and parses the search query.
   *
   * @param string $input
   *   The search keywords entered by the user.
   */
  protected function queryParseSearchExpression($input) {
    if (!isset($this->searchQuery)) {
      $this->parsed = TRUE;
      $this->searchQuery = \Drupal::service('database.replica')->select('search_index', 'i')->extend(ViewsSearchQuery::class);
      $this->searchQuery->searchExpression($input, $this->searchType);
      $this->searchQuery->publicParseSearchExpression();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Since attachment views don't validate the exposed input, parse the search
    // expression if required.
    if (!$this->parsed) {
      $this->queryParseSearchExpression($this->value);
    }
    $required = FALSE;
    if (!isset($this->searchQuery)) {
      $required = TRUE;
    }
    else {
      $words = $this->searchQuery->words();
      if (empty($words)) {
        $required = TRUE;
      }
    }
    if ($required) {
      if ($this->operator == 'required') {
        $this->query->addWhere($this->options['group'], 'FALSE');
      }
    }
    else {
      $search_index = $this->ensureMyTable();

      $search_condition = $this->view->query->getConnection()->condition('AND');

      // Create a new join to relate the 'search_total' table to our current
      // 'search_index' table.
      $definition = [
        'table' => 'search_total',
        'field' => 'word',
        'left_table' => $search_index,
        'left_field' => 'word',
      ];
      $join = Views::pluginManager('join')->createInstance('standard', $definition);
      $search_total = $this->query->addRelationship('search_total', $join, $search_index);

      // Add the search score field to the query.
      $this->search_score = $this->query->addField('', "$search_index.score * $search_total.count", 'score', ['function' => 'sum']);

      // Add the conditions set up by the search query to the views query.
      $search_condition->condition("$search_index.type", $this->searchType);
      $search_dataset = $this->query->addTable('node_search_dataset');
      $conditions = $this->searchQuery->conditions();
      $condition_conditions =& $conditions->conditions();
      foreach ($condition_conditions as $key => &$condition) {
        // Make sure we just look at real conditions.
        if (is_numeric($key)) {
          // Replace the conditions with the table alias of views.
          $this->searchQuery->conditionReplaceString('d.', "$search_dataset.", $condition);
        }
      }
      $search_conditions =& $search_condition->conditions();
      $search_conditions = array_merge($search_conditions, $condition_conditions);

      // Add the keyword conditions, as is done in
      // SearchQuery::prepareAndNormalize(), but simplified because we are
      // only concerned with relevance ranking so we do not need to normalize.
      $or = $this->view->query->getConnection()->condition('OR');
      foreach ($words as $word) {
        $or->condition("$search_index.word", $word);
      }
      $search_condition->condition($or);

      $this->query->addWhere($this->options['group'], $search_condition);

      // Add the GROUP BY and HAVING expressions to the query.
      $this->query->addGroupBy("$search_index.sid");
      $matches = $this->searchQuery->matches();
      $placeholder = $this->placeholder();
      $this->query->addHavingExpression($this->options['group'], "COUNT(*) >= $placeholder", [$placeholder => $matches]);
    }
    // Set to NULL to prevent PDO exception when views object is cached.
    $this->searchQuery = NULL;
  }

}
