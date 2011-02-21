<?php
// $Id: views_handler_argument_search.inc,v 1.1.2.2 2010/01/24 22:37:52 dereine Exp $

/**
 * Argument that accepts query keys for search.
 */
class views_handler_argument_search extends views_handler_argument {

  /**
   * Add this argument to the query.
   */
  function query() {
    $this->search_query = search_parse_query($this->argument);

    if (!isset($this->search_query) || empty($this->search_query[3])) {
      if ($this->operator == 'required') {
        $this->query->add_where($this->options['group'], '0');
      }
    }
    else {
      $search_index = $this->ensure_my_table();
      $this->search_query[2] = str_replace('i.', "$search_index.", $this->search_query[2]);

      // Create a new join to relate the 'search_total' table to our current 'search_index' table.
      $join = new views_join;
      $join->construct('search_total', $search_index, 'word', 'word');
      $search_total = $this->query->add_relationship('search_total', $join, $search_index);

      $this->search_score = $this->query->add_field('', "SUM($search_index.score * $search_total.count)", 'score', array('aggregate' => TRUE));

      $this->query->add_where($this->options['group'], $this->search_query[2], $this->search_query[3]);

      if (empty($this->query->relationships[$this->relationship])) {
        $base_table = $this->query->base_table;
      }
      else {
        $base_table = $this->query->relationships[$this->relationship]['base'];
      }
      $this->query->add_where($this->options['group'], "$search_index.type = '%s'", $base_table);
      if (!$this->search_query[5]) {
        $search_dataset = $this->query->add_table('search_dataset');
        $this->search_query[0] = str_replace('d.', "$search_dataset.", $this->search_query[0]);
        $this->query->add_where($this->options['group'], $this->search_query[0], $this->search_query[1]);
      }

      $this->query->add_groupby("$search_index.sid");
      $this->query->add_having($this->options['group'], 'COUNT(*)', $this->search_query[4], '>=');
    }
  }
}
