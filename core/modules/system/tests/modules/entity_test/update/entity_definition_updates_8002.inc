<?php

/**
 * @file
 * Defines the 8002 db update for the "entity_definition_updates" group.
 */

declare(strict_types=1);

require_once 'entity_definition_updates_8001.inc';

/**
 * Makes the 'user_id' field single and migrate its data.
 */
function entity_test_update_8002(): void {
  // To update the field schema we need to have no field data in the storage,
  // thus we retrieve it, delete it from storage, and write it back to the
  // storage after updating the schema.
  $database = \Drupal::database();

  // Retrieve existing entity data.
  $query = $database->select('entity_test__user_id', 'et')
    ->fields('et', ['entity_id', 'user_id_target_id']);
  $query->condition('et.delta', 0);
  $user_ids = $query->execute()->fetchAllKeyed();

  // Remove data from the storage.
  $database->truncate('entity_test__user_id')->execute();

  // Update definitions and schema.
  $manager = \Drupal::entityDefinitionUpdateManager();
  $storage_definition = $manager->getFieldStorageDefinition('user_id', 'entity_test');
  $storage_definition->setCardinality(1);
  $manager->updateFieldStorageDefinition($storage_definition);

  // Restore entity data in the new schema.
  foreach ($user_ids as $id => $user_id) {
    $database->update('entity_test')
      ->fields(['user_id' => $user_id])
      ->condition('id', $id)
      ->execute();
  }
}
