<?php

/**
 * @file
 * Definition of views_handler_argument_group_by_numeric.
 */

/**
 * Simple handler for arguments using group by.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_group_by_numeric extends views_handler_argument  {
  function query($group_by = FALSE) {
    $this->ensure_my_table();
    $field = $this->get_field();
    $placeholder = $this->placeholder();

    $this->query->add_having_expression(0, "$field = $placeholder", array($placeholder => $this->argument));
  }

  function ui_name($short = FALSE) {
    return $this->get_field(parent::ui_name($short));
  }

  function get_sort_name() {
    return t('Numerical', array(), array('context' => 'Sort order'));
  }
}
