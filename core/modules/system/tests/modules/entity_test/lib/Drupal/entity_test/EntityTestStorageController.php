<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTestStorageController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\FieldableDatabaseStorageController;

/**
 * Defines the controller class for the test entity.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for test entities.
 */
class EntityTestStorageController extends FieldableDatabaseStorageController {

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
