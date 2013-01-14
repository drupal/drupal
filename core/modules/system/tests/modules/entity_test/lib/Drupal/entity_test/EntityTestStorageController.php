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
   * Implements \Drupal\Core\Entity\DataBaseStorageControllerNG::baseFieldDefinitions().
   */
  public function baseFieldDefinitions() {
    $fields['id'] = array(
      'label' => t('ID'),
      'description' => t('The ID of the test entity.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The UUID of the test entity.'),
      'type' => 'string_field',
    );
    $fields['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The language code of the test entity.'),
      'type' => 'language_field',
    );
    $fields['name'] = array(
      'label' => t('Name'),
      'description' => t('The name of the test entity.'),
      'type' => 'string_field',
      'translatable' => TRUE,
    );
    $fields['user_id'] = array(
      'label' => t('User ID'),
      'description' => t('The ID of the associated user.'),
      'type' => 'entityreference_field',
      'settings' => array('entity type' => 'user'),
      'translatable' => TRUE,
    );
    return $fields;
  }
}
