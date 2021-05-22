<?php

/**
 * @file
 * Defines an entity type.
 */

/**
 * Implements hook_entity_type_alter().
 */
function field_test_entity_type_alter(array &$entity_types) {
  /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
  foreach (field_test_entity_info_translatable() as $entity_type => $translatable) {
    $entity_types[$entity_type]->set('translatable', $translatable);
  }
}

/**
 * Helper function to enable entity translations.
 */
function field_test_entity_info_translatable($entity_type_id = NULL, $translatable = NULL) {
  static $stored_value = [];
  if (isset($entity_type_id)) {
    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $entity_type = $entity_definition_update_manager->getEntityType($entity_type_id);
    $stored_value[$entity_type_id] = $translatable;
    if ($translatable != $entity_type->isTranslatable()) {
      $entity_definition_update_manager->uninstallEntityType($entity_type);
      $entity_type->set('translatable', $translatable);
      $entity_definition_update_manager->installEntityType($entity_type);
    }
  }
  return $stored_value;
}
