<?php

/**
 * @file
 */

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldPurger;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal\Core\Field\FieldPurger::purgeBatch() instead.
 *
 * @see https://www.drupal.org/node/3494023
 */
function field_purge_batch($batch_size, $field_storage_unique_id = NULL): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal\Core\Field\FieldPurger::purgeBatch() instead. See https://www.drupal.org/node/3494023', E_USER_DEPRECATED);
  \Drupal::service(FieldPurger::class)->purgeBatch($batch_size, $field_storage_unique_id);
}

/**
 * Purges a field record from the database.
 *
 * This function assumes all data for the field has already been purged and
 * should only be called by field_purge_batch().
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 *   The field to purge.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3494023
 */
function field_purge_field(FieldDefinitionInterface $field): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3494023', E_USER_DEPRECATED);
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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3494023
 */
function field_purge_field_storage(FieldStorageDefinitionInterface $field_storage): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3494023', E_USER_DEPRECATED);
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
