<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTestRevStorageController.
 */

namespace Drupal\entity_test;

use Drupal\entity_test\EntityTestStorageController;

/**
 * Defines the controller class for the test entity.
 *
 * This extends the Drupal\entity_test\EntityTestStorageController class, adding
 * required special handling for test entities with revision support.
 */
class EntityTestRevStorageController extends EntityTestStorageController {

  /**
   * Overrides \Drupal\entity_test\EntityTestStorageController::baseFieldDefinitions().
   */
  public function baseFieldDefinitions() {
    $fields = parent::baseFieldDefinitions();
    $fields['revision_id'] = array(
      'label' => t('ID'),
      'description' => t('The version id of the test entity.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    return $fields;
  }
}
