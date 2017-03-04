<?php

/**
 * @file
 * Defines an entity type.
 */

/**
 * Implements hook_entity_type_alter().
 */
function field_test_entity_type_alter(array &$entity_types) {
  /** @var $entity_types \Drupal\Core\Entity\EntityTypeInterface[] */
  foreach (field_test_entity_info_translatable() as $entity_type => $translatable) {
    $entity_types[$entity_type]->set('translatable', $translatable);
  }
}

/**
 * Helper function to enable entity translations.
 */
function field_test_entity_info_translatable($entity_type_id = NULL, $translatable = NULL) {
  $stored_value = &drupal_static(__FUNCTION__, []);
  if (isset($entity_type_id)) {
    $entity_manager = \Drupal::entityManager();
    $original = $entity_manager->getDefinition($entity_type_id);
    $stored_value[$entity_type_id] = $translatable;
    if ($translatable != $original->isTranslatable()) {
      $entity_manager->clearCachedDefinitions();
      $entity_type = $entity_manager->getDefinition($entity_type_id);
      $entity_manager->onEntityTypeUpdate($entity_type, $original);
    }
  }
  return $stored_value;
}
