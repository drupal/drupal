<?php

/**
 * @file
 * Provides support for field data purge after mass deletion.
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field\FieldException;

/**
 * @defgroup field_purge Field API bulk data deletion
 * @{
 * Cleans up after Field API bulk deletion operations.
 *
 * Field API provides functions for deleting data attached to individual
 * entities as well as deleting entire fields or field instances in a single
 * operation.
 *
 * When a single entity is deleted, the Entity storage performs the
 * following operations:
 * - Invoking the FieldItemListInterface delete() method for each field on the
 *   entity. A file field type might use this method to delete uploaded files
 *   from the filesystem.
 * - Removing the data from storage.
 * - Invoking the global hook_entity_delete() for all modules that implement it.
 *   Each hook implementation receives the entity being deleted and can operate
 *   on whichever subset of the entity's bundle's fields it chooses to.
 *
 * Similar operations are performed on deletion of a single entity revision.
 *
 * When a field, bundle, or field instance is deleted, it is not practical to
 * perform those operations immediately on every affected entity in a single
 * page request; there could be thousands or millions of them. Instead, the
 * appropriate field data items, instances, and/or fields are marked as deleted
 * so that subsequent load or query operations will not return them. Later, a
 * separate process cleans up, or "purges", the marked-as-deleted data by going
 * through the three-step process described above and, finally, removing deleted
 * field and instance records.
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
 * contains the field data for whichever field instance is currently being
 * purged.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Purges a batch of deleted Field API data, instances, or fields.
 *
 * This function will purge deleted field data in batches. The batch size
 * is defined as an argument to the function, and once each batch is finished,
 * it continues with the next batch until all have completed. If a deleted field
 * instance with no remaining data records is found, the instance itself will
 * be purged. If a deleted field with no remaining field instances is found, the
 * field itself will be purged.
 *
 * @param $batch_size
 *   The maximum number of field data records to purge before returning.
 * @param string $field_storage_uuid
 *   (optional) Limit the purge to a specific field storage.
 */
function field_purge_batch($batch_size, $field_storage_uuid = NULL) {
  $properties = array(
    'deleted' => TRUE,
    'include_deleted' => TRUE,
  );
  if ($field_storage_uuid) {
    $properties['field_storage_uuid'] = $field_storage_uuid;
  }
  $instances = entity_load_multiple_by_properties('field_instance_config', $properties);

  $info = \Drupal::entityManager()->getDefinitions();
  foreach ($instances as $instance) {
    $entity_type = $instance->entity_type;

    // We cannot purge anything if the entity type is unknown (e.g. the
    // providing module was uninstalled).
    // @todo Revisit after https://drupal.org/node/2080823.
    if (!isset($info[$entity_type])) {
      continue;
    }

    $count_purged = \Drupal::entityManager()->getStorage($entity_type)->purgeFieldData($instance, $batch_size);
    if ($count_purged < $batch_size || $count_purged == 0) {
      // No field data remains for the instance, so we can remove it.
      field_purge_instance($instance);
    }
    $batch_size -= $count_purged;
    // Only delete up to the maximum number of records.
    if ($batch_size == 0) {
      break;
    }
  }

  // Retrieve all deleted field storages. Any that have no instances can be
  // purged.
  $deleted_storages = \Drupal::state()->get('field.storage.deleted') ?: array();
  foreach ($deleted_storages as $field_storage) {
    $field_storage = new FieldStorageConfig($field_storage);
    if ($field_storage_uuid && $field_storage->uuid() != $field_storage_uuid) {
      // If a specific UUID is provided, only purge the corresponding field.
      continue;
    }

    // We cannot purge anything if the entity type is unknown (e.g. the
    // providing module was uninstalled).
    // @todo Revisit after https://drupal.org/node/2080823.
    if (!isset($info[$field_storage->entity_type])) {
      continue;
    }

    $instances = entity_load_multiple_by_properties('field_instance_config', array('field_storage_uuid' => $field_storage->uuid(), 'include_deleted' => TRUE));
    if (empty($instances)) {
      field_purge_field_storage($field_storage);
    }
  }
}

/**
 * Purges a field instance record from the database.
 *
 * This function assumes all data for the instance has already been purged and
 * should only be called by field_purge_batch().
 *
 * @param $instance
 *   The instance record to purge.
 */
function field_purge_instance($instance) {
  $state = \Drupal::state();
  $deleted_instances = $state->get('field.instance.deleted');
  unset($deleted_instances[$instance->uuid()]);
  $state->set('field.instance.deleted', $deleted_instances);

  // Invoke external hooks after the cache is cleared for API consistency.
  \Drupal::moduleHandler()->invokeAll('field_purge_instance', array($instance));
}

/**
 * Purges a field record from the database.
 *
 * This function assumes all instances for the field has already been purged,
 * and should only be called by field_purge_batch().
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field storage to purge.
 */
function field_purge_field_storage(FieldStorageConfigInterface $field_storage) {
  $instances = entity_load_multiple_by_properties('field_instance_config', array('field_storage_uuid' => $field_storage->uuid(), 'include_deleted' => TRUE));
  if (count($instances) > 0) {
    throw new FieldException(t('Attempt to purge a field storage @field_name that still has instances.', array('@field_name' => $field_storage->getName())));
  }

  $state = \Drupal::state();
  $deleted_storages = $state->get('field.storage.deleted');
  unset($deleted_storages[$field_storage->uuid()]);
  $state->set('field.storage.deleted', $deleted_storages);

  // Notify the storage layer.
  \Drupal::entityManager()->getStorage($field_storage->entity_type)->finalizePurge($field_storage);

  // Invoke external hooks after the cache is cleared for API consistency.
  \Drupal::moduleHandler()->invokeAll('field_purge_field_storage', array($field_storage));
}

/**
 * @} End of "defgroup field_purge".
 */
