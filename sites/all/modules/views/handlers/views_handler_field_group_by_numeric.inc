<?php
// $Id: views_handler_field_group_by_numeric.inc,v 1.1.4.6 2010/11/07 11:36:59 dereine Exp $

/**
 * Handler for GROUP BY on simple numeric fields.
 */
class views_handler_field_group_by_numeric extends views_handler_field_numeric {
  function init(&$view, &$options) {
    parent::init($view, $options);

    // Initialize the original handler.
    $this->handler = views_get_handler($options['table'], $options['field'], 'field');
    $this->handler->init($view, $options);
  }

  /**
   * Called to add the field to a query.
   */
  function query() {
    $this->ensure_my_table();
    // Add the field, taking care of any aggregation that may affect it.
    $params = array(
      'function' => $this->options['group_type'],
    );

    $this->field_alias = $this->query->add_field($this->table_alias, $this->real_field, NULL, $params);
    $this->add_additional_fields();
  }

  /**
   * Called to determine what to tell the clicksorter.
   */
  function click_sort($order) {
    $params = array(
      'function' => $this->options['group_type'],
    );

    $this->query->add_orderby($this->table, $this->field, $order, $this->field_alias, $params);
  }

  function ui_name($short = FALSE) {
    return $this->get_field(parent::ui_name($short));
  }
}
