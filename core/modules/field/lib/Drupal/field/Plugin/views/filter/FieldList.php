<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\views\filter\FieldList.
 */

namespace Drupal\field\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("field_list")
 */
class FieldList extends ManyToOne {

  public function getValueOptions() {
    $field = field_info_field($this->definition['field_name']);
    $this->value_options = list_allowed_values($field);
  }

}
