<?php

/**
 * @file
 * Definition of views_handler_filter_group_by_numeric.
 */

/**
 * Simple filter to handle greater than/less than filters
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_group_by_numeric extends views_handler_filter_numeric {
  function query() {
    $this->ensure_my_table();
    $field = $this->get_field();

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }
  function op_between($field) {
    $placeholder_min = $this->placeholder();
    $placeholder_max = $this->placeholder();
    if ($this->operator == 'between') {
      $this->query->add_having_expression($this->options['group'], "$field >= $placeholder_min", array($placeholder_min => $this->value['min']));
      $this->query->add_having_expression($this->options['group'], "$field <= $placeholder_max", array($placeholder_max => $this->value['max']));
    }
    else {
      $this->query->add_having_expression($this->options['group'], "$field <= $placeholder_min OR $field >= $placeholder_max", array($placeholder_min => $this->value['min'], $placeholder_max => $this->value['max']));
    }
  }

  function op_simple($field) {
    $placeholder = $this->placeholder();
    $this->query->add_having_expression($this->options['group'], "$field $this->operator $placeholder", array($placeholder => $this->value['value']));
  }

  function op_empty($field) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->add_having_expression($this->options['group'], "$field $operator");
  }

  function ui_name($short = FALSE) {
    return $this->get_field(parent::ui_name($short));
  }

  function can_group() { return FALSE; }
}
