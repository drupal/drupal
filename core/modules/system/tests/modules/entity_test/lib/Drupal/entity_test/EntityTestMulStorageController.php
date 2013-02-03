<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTestMulStorageController.
 */

namespace Drupal\entity_test;

use Drupal\entity_test\EntityTestStorageController;

/**
 * Defines the controller class for the test entity.
 *
 * This extends the Drupal\entity_test\EntityTestStorageController class, adding
 * required special handling for test entities with multilingual property
 * support.
 */
class EntityTestMulStorageController extends EntityTestStorageController {

  /**
   * Overrides \Drupal\entity_test\EntityTestStorageController::baseFieldDefinitions().
   */
  public function baseFieldDefinitions() {
    $fields = parent::baseFieldDefinitions();
    $fields['default_langcode'] = array(
      'label' => t('Default language'),
      'description' => t('Flag to indicate whether this is the default language.'),
      'type' => 'boolean_field',
    );
    return $fields;
  }
}
