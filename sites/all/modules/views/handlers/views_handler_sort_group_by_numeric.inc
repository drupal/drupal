<?php
// $Id: views_handler_sort_group_by_numeric.inc,v 1.1.4.4 2010/11/30 21:14:22 merlinofchaos Exp $
/**
 * Handler for GROUP BY on simple numeric fields.
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

  function ui_name() {
    return $this->get_field(parent::ui_name());
  }
}
