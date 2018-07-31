<?php

/**
 * @file
 * Defines the 8400 db update for the "entity_rev_pub_updates" group.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Add the 'published' entity key to entity_test_update.
 */
function entity_test_update_update_8400() {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();

  // Add the published entity key to the entity_test_update entity type.
  $entity_type = $definition_update_manager->getEntityType('entity_test_update');

  $entity_keys = $entity_type->getKeys();
  $entity_keys['published'] = 'status';
  $entity_type->set('entity_keys', $entity_keys);
  $definition_update_manager->updateEntityType($entity_type);

  // Add the status field.
  $status = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Publishing status'))
    ->setDescription(t('A boolean indicating the published state.'))
    ->setRevisionable(TRUE)
    ->setTranslatable(TRUE)
    ->setDefaultValue(TRUE);

  $has_content_translation_status_field = \Drupal::moduleHandler()->moduleExists('content_translation') && $definition_update_manager->getFieldStorageDefinition('content_translation_status', 'entity_test_update');
  if ($has_content_translation_status_field) {
    $status->setInitialValueFromField('content_translation_status', TRUE);
  }
  else {
    $status->setInitialValue(TRUE);
  }
  $definition_update_manager->installFieldStorageDefinition('status', 'entity_test_update', 'entity_test_update', $status);

  // Uninstall the 'content_translation_status' field if needed.
  $database = \Drupal::database();
  if ($has_content_translation_status_field) {
    // First we have to remove the field data.
    $database->update($entity_type->getDataTable())
      ->fields(['content_translation_status' => NULL])
      ->execute();

    // A site may have disabled revisionability for this entity type.
    if ($entity_type->isRevisionable()) {
      $database->update($entity_type->getRevisionDataTable())
        ->fields(['content_translation_status' => NULL])
        ->execute();
    }

    $content_translation_status = $definition_update_manager->getFieldStorageDefinition('content_translation_status', 'entity_test_update');
    $definition_update_manager->uninstallFieldStorageDefinition($content_translation_status);
  }
}
