<?php

/**
 * @file
 * Definition of views_handler_filter_field_list.
 */

namespace Drupal\field\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "field_list"
 * )
 */
class FieldList extends InOperator {
  function get_value_options() {
    $field = field_info_field($this->definition['field_name']);
    $this->value_options = list_allowed_values($field);
  }
}
