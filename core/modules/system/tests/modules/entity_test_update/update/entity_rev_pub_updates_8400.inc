<?php

/**
 * @file
 * Defines the 8400 db update for the "entity_rev_pub_updates" group.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Implements hook_update_dependencies().
 */
function entity_test_update_update_dependencies() {
  // The update function that adds the status field must run after
  // content_translation_update_8400() which fixes NULL values for the
  // 'content_translation_status' field.
  $dependencies['entity_test_update'][8400] = [
    'content_translation' => 8400,
  ];

  return $dependencies;
}

/**
 * Add the 'published' and revisionable metadata fields to entity_test_update.
 */
function entity_test_update_update_8400() {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();

  // Add the published entity key and revisionable metadata fields to the
  // entity_test_update entity type.
  $entity_type = $definition_update_manager->getEntityType('entity_test_update');

  $entity_keys = $entity_type->getKeys();
  $entity_keys['published'] = 'status';
  $entity_type->set('entity_keys', $entity_keys);

  $revision_metadata_keys = [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
    'revision_default' => 'revision_default',
  ];
  $entity_type->set('revision_metadata_keys', $revision_metadata_keys);

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
    $status->setInitialValueFromField('content_translation_status');
  }
  else {
    $status->setInitialValue(TRUE);
  }
  $definition_update_manager->installFieldStorageDefinition('status', 'entity_test_update', 'entity_test_update', $status);

  // Add the revision metadata fields.
  $revision_created = BaseFieldDefinition::create('created')
    ->setLabel(t('Revision create time'))
    ->setDescription(t('The time that the current revision was created.'))
    ->setRevisionable(TRUE);
  $definition_update_manager->installFieldStorageDefinition('revision_created', 'entity_test_update', 'entity_test_update', $revision_created);

  $revision_user = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Revision user'))
    ->setDescription(t('The user ID of the author of the current revision.'))
    ->setSetting('target_type', 'user')
    ->setRevisionable(TRUE);
  $definition_update_manager->installFieldStorageDefinition('revision_user', 'entity_test_update', 'entity_test_update', $revision_user);

  $revision_log_message = BaseFieldDefinition::create('string_long')
    ->setLabel(t('Revision log message'))
    ->setDescription(t('Briefly describe the changes you have made.'))
    ->setRevisionable(TRUE)
    ->setDefaultValue('')
    ->setDisplayOptions('form', [
      'type' => 'string_textarea',
      'weight' => 25,
      'settings' => [
        'rows' => 4,
      ],
    ]);
  $definition_update_manager->installFieldStorageDefinition('revision_log_message', 'entity_test_update', 'entity_test_update', $revision_log_message);

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
