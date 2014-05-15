<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\views\filter\FieldList.
 */

namespace Drupal\field\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("field_list")
 */
class FieldList extends ManyToOne {

  public function getValueOptions() {
    $field_storage_definitions = \Drupal::entityManager()->getFieldStorageDefinitions($this->definition['entity_type']);
    $field = $field_storage_definitions[$this->definition['field_name']];
    $this->value_options = list_allowed_values($field);
  }

}
