<?php

/**
 * @file
 * Definition of views_handler_sort_group_by_numeric.
 */

/**
 * Handler for GROUP BY on simple numeric fields.
 *
 * @ingroup views_sort_handlers
 */
class views_handler_sort_group_by_numeric extends views_handler_sort {
  function init(&$view, &$options) {
    parent::init($view, $options);

    // Initialize the original handler.
    $this->handler = views_get_handler($options['table'], $options['field'], 'sort');
    $this->handler->init($view, $options);
  }

  /**
   * Called to add the field to a query.
   */
  function query() {
    $this->ensure_my_table();

    $params = array(
      'function' => $this->options['group_type'],
    );

    $this->query->add_orderby($this->table_alias, $this->real_field, $this->options['order'], NULL, $params);
  }

  function ui_name($short = FALSE) {
    return $this->get_field(parent::ui_name($short));
  }
}
