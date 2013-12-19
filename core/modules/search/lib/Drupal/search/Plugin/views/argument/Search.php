<?php

/**
 * @file
 * Definition of Drupal\search\Plugin\views\argument\Search.
 */

namespace Drupal\search\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Argument that accepts query keys for search.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("search")
 */
class Search extends ArgumentPluginBase {

  /**
   * Make sure that parseSearchExpression is run and everything is set up.
   *
   * @param $input
   *    The search phrase which was input by the user.
   */
  function query_parse_search_expression($input) {
    if (!isset($this->search_query)) {
      $this->search_query = db_select('search_index', 'i', array('target' => 'slave'))->extend('Drupal\search\ViewsSearchQuery');
      $this->search_query->searchExpression($input, $this->view->base_table);
      $this->search_query->publicParseSearchExpression();
    }
  }

  /**
   * Add this argument to the query.
   */
  public function query($group_by = FALSE) {
    $required = FALSE;
    $this->query_parse_search_expression($this->argument);
    if (!isset($this->search_query)) {
      $required = TRUE;
    }
    else {
      $words = $this->search_query->words();
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

      $search_condition = db_and();

      // Create a new join to relate the 'search_total' table to our current 'search_index' table.
      $definition = array(
        'table' => 'search_total',
        'field' => 'word',
        'left_table' => $search_index,
        'left_field' => 'word',
      );
      $join = \Drupal::service()->get('plugin.manager.views.join')->createInstance('standard', $definition);
      $search_total = $this->query->addRelationship('search_total', $join, $search_index);

      $this->search_score = $this->query->addField('', "SUM($search_index.score * $search_total.count)", 'score', array('aggregate' => TRUE));

      if (empty($this->query->relationships[$this->relationship])) {
        $base_table = $this->view->storage->get('base_table');
      }
      else {
        $base_table = $this->query->relationships[$this->relationship]['base'];
      }
      $search_condition->condition("$search_index.type", $base_table);

      if (!$this->search_query->simple()) {
        $search_dataset = $this->query->addTable('search_dataset');
        $conditions = $this->search_query->conditions();
        $condition_conditions =& $conditions->conditions();
        foreach ($condition_conditions  as $key => &$condition) {
          // Make sure we just look at real conditions.
          if (is_numeric($key)) {
            // Replace the conditions with the table alias of views.
            $this->search_query->condition_replace_string('d.', "$search_dataset.", $condition);
          }
        }
        $search_conditions =& $search_condition->conditions();
        $search_conditions = array_merge($search_conditions, $condition_conditions);
      }
      else {
        // Stores each condition, so and/or on the filter level will still work.
        $or = db_or();
        foreach ($words as $word) {
          $or->condition("$search_index.word", $word);
        }

        $search_condition->condition($or);
      }

      $this->query->addWhere(0, $search_condition);
      $this->query->addGroupBy("$search_index.sid");
      $matches = $this->search_query->matches();
      $placeholder = $this->placeholder();
      $this->query->addHavingExpression(0, "COUNT(*) >= $placeholder", array($placeholder => $matches));
    }
  }

}
