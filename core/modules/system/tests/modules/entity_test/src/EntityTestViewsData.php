<?php

namespace Drupal\entity_test;

use Drupal\Component\Utility\NestedArray;
use Drupal\views\EntityViewsData;

/**
 * Provides a view to override views data for test entity types.
 */
class EntityTestViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $views_data = parent::getViewsData();

    if ($this->entityType->id() === 'entity_test_computed_field') {
      $views_data['entity_test_computed_field']['computed_string_field'] = [
        'title' => $this->t('Computed String Field'),
        'field' => [
          'id' => 'field',
          'default_formatter' => 'string',
          'field_name' => 'computed_string_field',
        ],
      ];
    }

    if ($this->entityType->id() != 'entity_test') {
      return $views_data;
    }

    $views_data = NestedArray::mergeDeep($views_data, \Drupal::state()->get('entity_test.views_data', []));

    return $views_data;
  }

}
