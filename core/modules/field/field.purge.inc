<?php

/**
 * @file
 * Provides support for field data purge after mass deletion.
 */

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * @defgroup field_purge Field API bulk data deletion
 * @{
 * Cleans up after Field API bulk deletion operations.
 *
 * Field API provides functions for deleting data attached to individual
 * entities as well as deleting entire fields or field storages in a single
 * operation.
 *
 * When a single entity is deleted, the Entity storage performs the
 * following operations:
 * - Invoking the method \Drupal\Core\Field\FieldItemListInterface::delete() for
 *   each field on the entity. A file field type might use this method to delete
 *   uploaded files from the filesystem.
 * - Removing the data from storage.
 * - Invoking the global hook_entity_delete() for all modules that implement it.
 *   Each hook implementation receives the entity being deleted and can operate
 *   on whichever subset of the entity's bundle's fields it chooses to.
 *
 * Similar operations are performed on deletion of a single entity revision.
 *
 * When a bundle, field or field storage is deleted, it is not practical to
 * perform those operations immediately on every affected entity in a single
 * page request; there could be thousands or millions of them. Instead, the
 * appropriate field data items, fields, and/or field storages are marked as
 * deleted so that subsequent load or query operations will not return them.
 * Later, a separate process cleans up, or "purges", the marked-as-deleted data
 * by going through the three-step process described above and, finally,
 * removing deleted field storage and field records.
 *
 * Purging field data is made somewhat tricky by the fact that, while
 * $entity->delete() has a complete entity to pass to the various deletion
 * steps, the Field API purge process only has the field data it has previously
 * stored. It cannot reconstruct complete original entities to pass to the
 * deletion operations. It is even possible that the original entity to which
 * some Field API data was attached has been itself deleted before the field
 * purge operation takes place.
 *
 * Field API resolves this problem by using stub entities during purge
 * operations, containing only the information from the original entity that
 * Field API knows about: entity type, ID, revision ID, and bundle. It also
 * contains the field data for whichever field is currently being purged.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Purges a batch of deleted Field API data, field storages, or fields.
 *
 * This function will purge deleted field data in batches. The batch size
 * is defined as an argument to the function, and once each batch is finished,
 * it continues with the next batch until all have completed. If a deleted field
 * with no remaining data records is found, the field itself will
 * be purged. If a deleted field storage with no remaining fields is found, the
 * field storage itself will be purged.
 *
 * @param int $batch_size
 *   The maximum number of field data records to purge before returning.
 * @param string $field_storage_unique_id
 *   (optional) Limit the purge to a specific field storage. Defaults to NULL.
 */
function field_purge_batch($batch_size, $field_storage_unique_id = NULL) {
  /** @var \Drupal\Core\Field\DeletedFieldsRepositoryInterface $deleted_fields_repository */
  $deleted_fields_repository = \Drupal::service('entity_field.deleted_fields_repository');

  $fields = $deleted_fields_repository->getFieldDefinitions($field_storage_unique_id);

  foreach ($fields as $field) {
    $entity_type = $field->getTargetEntityTypeId();

    $count_purged = \Drupal::entityTypeManager()->getStorage($entity_type)->purgeFieldData($field, $batch_size);
    if ($count_purged < $batch_size || $count_purged == 0) {
      // No field data remains for the field, so we can remove it.
      field_purge_field($field);
    }
    $batch_size -= $count_purged;
    // Only delete up to the maximum number of records.
    if ($batch_size == 0) {
      break;
    }
  }

  // Retrieve all deleted field storages. Any that have no fields can be purged.
  foreach ($deleted_fields_repository->getFieldStorageDefinitions() as $field_storage) {
    if ($field_storage_unique_id && $field_storage->getUniqueStorageIdentifier() != $field_storage_unique_id) {
      // If a specific UUID is provided, only purge the corresponding field.
      continue;
    }

    $fields = $deleted_fields_repository->getFieldDefinitions($field_storage->getUniqueStorageIdentifier());
    if (empty($fields)) {
      field_purge_field_storage($field_storage);
    }
  }
}

/**
 * Purges a field record from the database.
 *
 * This function assumes all data for the field has already been purged and
 * should only be called by field_purge_batch().
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 *   The field to purge.
 */
function field_purge_field(FieldDefinitionInterface $field) {
  /** @var \Drupal\Core\Field\DeletedFieldsRepositoryInterface $deleted_fields_repository */
  $deleted_fields_repository = \Drupal::service('entity_field.deleted_fields_repository');
  $deleted_fields_repository->removeFieldDefinition($field);

  // Invoke external hooks after the cache is cleared for API consistency.
  \Drupal::moduleHandler()->invokeAll('field_purge_field', [$field]);
}

/**
 * Purges a field record from the database.
 *
 * This function assumes all fields for the field storage has already been
 * purged, and should only be called by field_purge_batch().
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage
 *   The field storage to purge.
 *
 * @throws \Drupal\Core\Field\FieldException
 */
function field_purge_field_storage(FieldStorageDefinitionInterface $field_storage) {
  /** @var \Drupal\Core\Field\DeletedFieldsRepositoryInterface $deleted_fields_repository */
  $deleted_fields_repository = \Drupal::service('entity_field.deleted_fields_repository');

  $fields = $deleted_fields_repository->getFieldDefinitions($field_storage->getUniqueStorageIdentifier());
  if (count($fields) > 0) {
    throw new FieldException("Attempt to purge a field storage '{$field_storage->getName()}' that still has fields.");
  }

  $deleted_fields_repository->removeFieldStorageDefinition($field_storage);

  // Notify the storage layer.
  \Drupal::entityTypeManager()->getStorage($field_storage->getTargetEntityTypeId())->finalizePurge($field_storage);

  // Invoke external hooks after the cache is cleared for API consistency.
  \Drupal::moduleHandler()->invokeAll('field_purge_field_storage', [$field_storage]);
}

/**
 * @} End of "defgroup field_purge".
 */
