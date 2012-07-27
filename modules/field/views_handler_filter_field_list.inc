<?php

/**
 * @file
 * Definition of views_handler_filter_field_list.
 */

use Drupal\views\Plugins\views\filter\InOperator;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_field_list extends InOperator {
  function get_value_options() {
    $field = field_info_field($this->definition['field_name']);
    $this->value_options = list_allowed_values($field);
  }
}
