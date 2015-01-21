<?php

/**
 * @file
 * Contains Drupal\entity_test\EntityTestViewsData.
 */

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

    if ($this->entityType->id() != 'entity_test') {
      return $views_data;
    }

    $views_data = NestedArray::mergeDeep($views_data, \Drupal::state()->get('entity_test.views_data', []));

    return $views_data;
  }

}
