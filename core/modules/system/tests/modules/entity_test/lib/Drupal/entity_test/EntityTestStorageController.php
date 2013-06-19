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

  /**
   * {@inheritdoc}
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
      'type' => 'uuid_field',
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
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 32)),
      ),
    );
    $fields['type'] = array(
      'label' => t('Type'),
      'description' => t('The bundle of the test entity.'),
      'type' => 'string_field',
      'required' => TRUE,
      // @todo: Add allowed values validation.
    );
    $fields['user_id'] = array(
      'label' => t('User ID'),
      'description' => t('The ID of the associated user.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
      'translatable' => TRUE,
    );
    return $fields;
  }
}
