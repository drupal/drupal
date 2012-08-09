<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTestStorageController.
 */

namespace Drupal\entity_test;

use PDO;

use Drupal\entity\EntityInterface;
use Drupal\entity\DatabaseStorageController;

/**
 * Defines the controller class for the test entity.
 *
 * This extends the Drupal\entity\DatabaseStorageController class, adding
 * required special handling for test entities.
 */
class EntityTestStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\entity\DatabaseStorageController::buildQuery().
   */
  protected function buildQuery($ids, $conditions = array(), $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $conditions, $revision_id);

    if ($conditions) {
      // Reset conditions as the default storage controller applies them to the
      // base table.
      $query_conditions = &$query->conditions();
      $query_conditions = array('#conjunction' => 'AND');

      // Restore id conditions.
      if ($ids) {
        $query->condition("base.{$this->idKey}", $ids, 'IN');
      }

      // Conditions need to be applied the property data table.
      $query->addJoin('inner', 'entity_test_property_data', 'data', "base.{$this->idKey} = data.{$this->idKey}");
      $query->distinct(TRUE);

      // @todo We should not be using a condition to specify whether conditions
      // apply to the default language or not. We need to move this to a
      // separate parameter during the following API refactoring.
      // Default to the original entity language if not explicitly specified
      // otherwise.
      if (!array_key_exists('default_langcode', $conditions)) {
        $conditions['default_langcode'] = 1;
      }
      // If the 'default_langcode' flag is esplicitly not set, we do not care
      // whether the queried values are in the original entity language or not.
      elseif ($conditions['default_langcode'] === NULL) {
        unset($conditions['default_langcode']);
      }

      foreach ($conditions as $field => $value) {
        $query->condition('data.' . $field, $value);
      }
    }

    return $query;
  }

  /**
   * Overrides Drupal\entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    $data = db_select('entity_test_property_data', 'data', array('fetch' => PDO::FETCH_ASSOC))
      ->fields('data')
      ->condition('id', array_keys($queried_entities))
      ->orderBy('data.id')
      ->execute();

    foreach ($data as $values) {
      $entity = $queried_entities[$values['id']];
      $langcode = $values['langcode'];
      if (!empty($values['default_langcode'])) {
        $entity->setLangcode($langcode);
      }
      // Make sure only real properties are stored.
      unset($values['id'], $values['default_langcode']);
      $entity->setProperties($values, $langcode);
    }

    parent::attachLoad($queried_entities, $revision_id);
  }

  /**
   * Overrides Drupal\entity\DatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    $default_langcode = ($language = $entity->language()) ? $language->langcode : LANGUAGE_NOT_SPECIFIED;
    $langcodes = array_keys($entity->translations());
    $langcodes[] = $default_langcode;

    foreach ($langcodes as $langcode) {
      $properties = $entity->getProperties($langcode);

      $values = array(
        'id' => $entity->id(),
        'langcode' => $langcode,
        'default_langcode' => intval($default_langcode == $langcode),
      ) + $properties;

      db_merge('entity_test_property_data')
        ->fields($values)
        ->condition('id', $values['id'])
        ->condition('langcode', $values['langcode'])
        ->execute();
    }
  }

  /**
   * Overrides Drupal\entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    db_delete('entity_test_property_data')
      ->condition('id', array_keys($entities))
      ->execute();
  }
}
