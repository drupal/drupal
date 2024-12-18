<?php

/**
 * @file
 * Defines the 8001 db update for the "entity_definition_updates" group.
 */

declare(strict_types=1);

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Makes the 'user_id' field multiple and migrate its data.
 */
function entity_test_update_8001(): void {
  // To update the field schema we need to have no field data in the storage,
  // thus we retrieve it, delete it from storage, and write it back to the
  // storage after updating the schema.
  $database = \Drupal::database();

  // Retrieve existing field data.
  $user_ids = $database->select('entity_test', 'et')
    ->fields('et', ['id', 'user_id'])
    ->execute()
    ->fetchAllKeyed();

  // Remove data from the storage.
  $database->update('entity_test')
    ->fields(['user_id' => NULL])
    ->execute();

  // Update definitions and schema.
  $manager = \Drupal::entityDefinitionUpdateManager();
  $storage_definition = $manager->getFieldStorageDefinition('user_id', 'entity_test');
  $storage_definition->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
  $manager->updateFieldStorageDefinition($storage_definition);

  // Restore entity data in the new schema.
  $insert_query = $database->insert('entity_test__user_id')
    ->fields(['bundle', 'deleted', 'entity_id', 'revision_id', 'langcode', 'delta', 'user_id_target_id']);
  foreach ($user_ids as $id => $user_id) {
    $insert_query->values(['entity_test', 0, $id, $id, 'en', 0, $user_id]);
  }
  $insert_query->execute();
}
