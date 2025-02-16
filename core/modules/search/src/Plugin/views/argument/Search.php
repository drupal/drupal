<?php

namespace Drupal\search\Plugin\views\argument;

use Drupal\search\ViewsSearchQuery;
use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Argument handler for search keywords.
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'search',
)]
class Search extends ArgumentPluginBase {

  /**
   * A search query to use for parsing search keywords.
   *
   * @var \Drupal\search\ViewsSearchQuery
   */
  protected $searchQuery = NULL;

  /**
   * The search type name (value of {search_index}.type in the database).
   *
   * @var string
   */
  protected $searchType;

  /**
   * The search score.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public string $search_score;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->searchType = $this->definition['search_type'];
  }

  /**
   * Sets up and parses the search query.
   *
   * @param string $input
   *   The search keywords entered by the user.
   */
  protected function queryParseSearchExpression($input) {
    if (!isset($this->searchQuery)) {
      $this->searchQuery = \Drupal::service('database.replica')->select('search_index', 'i')->extend(ViewsSearchQuery::class);
      $this->searchQuery->searchExpression($input, $this->searchType);
      $this->searchQuery->publicParseSearchExpression();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $required = FALSE;
    $this->queryParseSearchExpression($this->argument);
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
        $this->query->addWhere(0, 'FALSE');
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

      // Add the GROUP BY and HAVING expressions to the query.
      $this->query->addWhere(0, $search_condition);
      $this->query->addGroupBy("$search_index.sid");
      $matches = $this->searchQuery->matches();
      $placeholder = $this->placeholder();
      $this->query->addHavingExpression(0, "COUNT(*) >= $placeholder", [$placeholder => $matches]);
    }

    // Set to NULL to prevent PDO exception when views object is cached
    // and to clear out memory.
    $this->searchQuery = NULL;
  }

}
