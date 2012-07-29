<?php

/**
 * @file
 * Definition of views_handler_filter_system_type.
 */

/**
 * Filter by system type.
 */
class views_handler_filter_system_type extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Type');
      // Enable filtering by type.
      $types = array();
      $types = db_query('SELECT DISTINCT(type) FROM {system} ORDER BY type')->fetchAllKeyed(0, 0);
      $this->value_options = $types;
    }
  }
}
