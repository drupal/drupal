<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTestStorageController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Defines the controller class for the test entity.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for test entities.
 */
class EntityTestStorageController extends DatabaseStorageControllerNG {

  /**
   * {@inheritdoc}
   */
  public function create(array $values) {
    if (empty($values['type'])) {
      $values['type'] = $this->entityType;
    }
    return parent::create($values);
  }

}
