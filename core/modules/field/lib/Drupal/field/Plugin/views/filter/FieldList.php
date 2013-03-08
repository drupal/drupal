<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\views\filter\FieldList.
 */

namespace Drupal\field\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\Component\Annotation\Plugin;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "field_list",
 *   module = "field"
 * )
 */
class FieldList extends ManyToOne {

  function get_value_options() {
    $field = field_info_field($this->definition['field_name']);
    $this->value_options = list_allowed_values($field);
  }

}
