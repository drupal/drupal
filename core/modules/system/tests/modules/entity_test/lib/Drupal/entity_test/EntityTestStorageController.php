<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTestStorageController.
 */

namespace Drupal\entity_test;

use PDO;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityFieldQuery;

/**
 * Defines the controller class for the test entity.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for test entities.
 */
class EntityTestStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildPropertyQuery().
   */
  protected function buildPropertyQuery(EntityFieldQuery $entity_query, array $values) {
    // @todo We should not be using a condition to specify whether conditions
    // apply to the default language or not. We need to move this to a
    // separate parameter during the following API refactoring.
    // Default to the original entity language if not explicitly specified
    // otherwise.
    if (!array_key_exists('default_langcode', $values)) {
      $values['default_langcode'] = 1;
    }
    // If the 'default_langcode' flag is esplicitly not set, we do not care
    // whether the queried values are in the original entity language or not.
    elseif ($values['default_langcode'] === NULL) {
      unset($values['default_langcode']);
    }

    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * Maps from storage records to entity objects.
   *
   * @return array
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records) {
    $records = parent::mapFromStorageRecords($records);

    // Load data of translatable properties.
    $this->attachPropertyData($records);
    return $records;
  }

  /**
   * Attaches property data in all languages for translatable properties.
   */
  protected function attachPropertyData(&$queried_entities) {
    $data = db_select('entity_test_property_data', 'data', array('fetch' => PDO::FETCH_ASSOC))
      ->fields('data')
      ->condition('id', array_keys($queried_entities))
      ->orderBy('data.id')
      ->execute();

    foreach ($data as $values) {
      $id = $values['id'];
      // Field values in default language are stored with
      // LANGUAGE_DEFAULT as key.
      $langcode = empty($values['default_langcode']) ? $values['langcode'] : LANGUAGE_DEFAULT;

      $queried_entities[$id]->name[$langcode][0]['value'] = $values['name'];
      $queried_entities[$id]->user_id[$langcode][0]['value'] = $values['user_id'];
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   *
   * Stores values of translatable properties.
   */
  protected function postSave(EntityInterface $entity, $update) {
    $default_langcode = $entity->language()->langcode;

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);

      $values = array(
        'id' => $entity->id(),
        'langcode' => $langcode,
        'default_langcode' => intval($default_langcode == $langcode),
        'name' => $translation->name->value,
        'user_id' => $translation->user_id->value,
      );

      db_merge('entity_test_property_data')
        ->fields($values)
        ->condition('id', $values['id'])
        ->condition('langcode', $values['langcode'])
        ->execute();
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    db_delete('entity_test_property_data')
      ->condition('id', array_keys($entities))
      ->execute();
  }

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
