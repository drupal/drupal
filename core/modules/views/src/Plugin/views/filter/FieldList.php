<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\filter\FieldList.
 */

namespace Drupal\views\Plugin\views\filter;

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
    $field_storage = $field_storage_definitions[$this->definition['field_name']];
    $this->valueOptions = list_allowed_values($field_storage);
  }

}
