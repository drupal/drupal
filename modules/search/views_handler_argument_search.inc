<?php

/**
 * @file
 * Definition of views_handler_argument_search.
 */

/**
 * Argument that accepts query keys for search.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_search extends views_handler_argument {

  /**
   * Take sure that parseSearchExpression is runned and everything is set up for it.
   *
   * @param $input
   *    The search phrase which was input by the user.
   */
  function query_parse_search_expression($input) {
    if (!isset($this->search_query)) {
      $this->search_query = db_select('search_index', 'i', array('target' => 'slave'))->extend('viewsSearchQuery');
      $this->search_query->searchExpression($input, $this->view->base_table);
      $this->search_query->publicParseSearchExpression();
    }
  }

  /**
   * Add this argument to the query.
   */
  function query($group_by = FALSE) {
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
        $this->query->add_where(0, 'FALSE');
      }
    }
    else {
      $search_index = $this->ensure_my_table();

      $search_condition = db_and();

      // Create a new join to relate the 'search_total' table to our current 'search_index' table.
      $join = new views_join;
      $join->construct('search_total', $search_index, 'word', 'word');
      $search_total = $this->query->add_relationship('search_total', $join, $search_index);

      $this->search_score = $this->query->add_field('', "SUM($search_index.score * $search_total.count)", 'score', array('aggregate' => TRUE));

      if (empty($this->query->relationships[$this->relationship])) {
        $base_table = $this->query->base_table;
      }
      else {
        $base_table = $this->query->relationships[$this->relationship]['base'];
      }
      $search_condition->condition("$search_index.type", $base_table);

      if (!$this->search_query->simple()) {
        $search_dataset = $this->query->add_table('search_dataset');
        $conditions = $this->search_query->conditions();
        $condition_conditions =& $conditions->conditions();
        foreach ($condition_conditions  as $key => &$condition) {
          // Take sure we just look at real conditions.
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

      $this->query->add_where(0, $search_condition);
      $this->query->add_groupby("$search_index.sid");
      $matches = $this->search_query->matches();
      $placeholder = $this->placeholder();
      $this->query->add_having_expression(0, "COUNT(*) >= $placeholder", array($placeholder => $matches));
    }
  }
}
