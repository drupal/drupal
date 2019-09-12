<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Site\Settings;

/**
 * Helper methods for EntityTypeListenerInterface.
 *
 * @see \Drupal\Core\Entity\EntityTypeListenerInterface
 *
 * @property \Drupal\Core\Entity\EntityTypeManagerInterface entityTypeManager
 * @property \Drupal\Core\Database\Connection database
 *
 * @internal
 */
trait SqlFieldableEntityTypeListenerTrait {

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $original_storage */
    $original_storage = $this->entityTypeManager->createHandlerInstance($original->getStorageClass(), $original);
    $has_data = $original_storage->hasData();

    // If 'progress' is not set, then this will be the first run of the batch.
    if (!isset($sandbox['progress'])) {
      // We cannot support updating the schema of an entity type from
      // revisionable to non-revisionable or translatable to non-translatable
      // because that can lead to unintended data loss.
      // @todo Add support for these conversions in case there is no data loss.
      //   @see https://www.drupal.org/project/drupal/issues/3024727
      $convert_rev_to_non_rev = $original->isRevisionable() && !$entity_type->isRevisionable();
      $convert_mul_to_non_mul = $original->isTranslatable() && !$entity_type->isTranslatable();
      if ($has_data && ($convert_rev_to_non_rev || $convert_mul_to_non_mul)) {
        throw new EntityStorageException('Converting an entity type from revisionable to non-revisionable or from translatable to non-translatable is not supported.');
      }

      // Check that the fields required by a revisionable entity type exist.
      if ($entity_type->isRevisionable() && !isset($field_storage_definitions[$entity_type->getKey('revision')])) {
        throw new EntityStorageException('Missing revision field.');
      }
      if ($entity_type->isRevisionable() && !isset($field_storage_definitions[$entity_type->getRevisionMetadataKey('revision_default')])) {
        throw new EntityStorageException('Missing revision_default field.');
      }

      // Check that the fields required by a translatable entity type exist.
      if ($entity_type->isTranslatable() && !isset($field_storage_definitions[$entity_type->getKey('langcode')])) {
        throw new EntityStorageException('Missing langcode field.');
      }
      if ($entity_type->isTranslatable() && !isset($field_storage_definitions[$entity_type->getKey('default_langcode')])) {
        throw new EntityStorageException('Missing default_langcode field.');
      }

      // Check that the fields required by a revisionable and translatable
      // entity type exist.
      if ($entity_type->isRevisionable() && $entity_type->isTranslatable() && !isset($field_storage_definitions[$entity_type->getKey('revision_translation_affected')])) {
        throw new EntityStorageException('Missing revision_translation_affected field.');
      }

      $this->preUpdateEntityTypeSchema($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
    }

    // Copy data from the original storage to the temporary one.
    if ($has_data) {
      $this->copyData($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
    }
    else {
      // If there is no existing data, we still need to run the
      // post-schema-update tasks.
      $sandbox['#finished'] = 1;
    }

    // If the data copying has finished successfully, allow the storage schema
    // to do any required cleanup tasks. For example, this process should take
    // care of transforming the temporary storage into the current storage.
    if ($sandbox['#finished'] == 1) {
      $this->postUpdateEntityTypeSchema($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
    }
  }

  /**
   * Allows subscribers to prepare their schema before data copying.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The updated entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The original entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storage_definitions
   *   The updated field storage definitions, including possibly new ones.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $original_field_storage_definitions
   *   The original field storage definitions.
   * @param array &$sandbox
   *   (optional) A sandbox array provided by a hook_update_N() implementation
   *   or a Batch API callback. If the entity schema update requires a data
   *   migration, this parameter is mandatory. Defaults to NULL.
   */
  protected function preUpdateEntityTypeSchema(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
  }

  /**
   * Allows subscribers to do any cleanup necessary after data copying.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The updated entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The original entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storage_definitions
   *   The updated field storage definitions, including possibly new ones.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $original_field_storage_definitions
   *   The original field storage definitions.
   * @param array &$sandbox
   *   (optional) A sandbox array provided by a hook_update_N() implementation
   *   or a Batch API callback. If the entity schema update requires a data
   *   migration, this parameter is mandatory. Defaults to NULL.
   */
  protected function postUpdateEntityTypeSchema(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
  }

  /**
   * Copies entity data from the original storage to the temporary one.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The updated entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The original entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storage_definitions
   *   The updated field storage definitions, including possibly new ones.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $original_field_storage_definitions
   *   The original field storage definitions.
   * @param array &$sandbox
   *   The sandbox array from a hook_update_N() implementation.
   */
  protected function copyData(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox) {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $id_key = $entity_type->getKey('id');
    $revision_id_key = $entity_type->getKey('revision');
    $revision_default_key = $entity_type->getRevisionMetadataKey('revision_default');
    $langcode_key = $entity_type->getKey('langcode');
    $default_langcode_key = $entity_type->getKey('default_langcode');
    $revision_translation_affected_key = $entity_type->getKey('revision_translation_affected');

    // If 'progress' is not set, then this will be the first run of the batch.
    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['current_id'] = -1;
    }

    // If the original entity type is revisionable, we need to copy all the
    // revisions.
    $load_revisions = $original->isRevisionable();
    if ($load_revisions) {
      $table_name = $original->getRevisionTable();
      $identifier_field = $revision_id_key;
    }
    else {
      $table_name = $original->getBaseTable();
      $identifier_field = $id_key;
    }

    // Get the next entity identifiers to migrate.
    // @todo Use an entity query when it is able to use the last installed
    //   entity type and field storage definitions.
    // @see https://www.drupal.org/project/drupal/issues/2554235
    $step_size = Settings::get('entity_update_batch_size', 50);
    $entity_identifiers = $this->database->select($table_name, 't')
      ->condition("t.$identifier_field", $sandbox['current_id'], '>')
      ->fields('t', [$identifier_field])
      ->orderBy($identifier_field, 'ASC')
      ->range(0, $step_size)
      ->execute()
      ->fetchCol();

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $load_revisions ? $this->storage->loadMultipleRevisions($entity_identifiers) : $this->storage->loadMultiple($entity_identifiers);

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $temporary_storage */
    $temporary_storage = $this->entityTypeManager->createHandlerInstance($entity_type->getStorageClass(), $entity_type);
    $temporary_storage->setEntityType($entity_type);
    $temporary_storage->setFieldStorageDefinitions($field_storage_definitions);
    $temporary_storage->setTableMapping($sandbox['temporary_table_mapping']);

    foreach ($entities as $identifier => $entity) {
      try {
        if (!$original->isRevisionable() && $entity_type->isRevisionable()) {
          // Set the revision ID to be same as the entity ID.
          $entity->set($revision_id_key, $entity->id());

          // We had no revisions so far, so the existing data belongs to the
          // default revision now.
          $entity->set($revision_default_key, TRUE);
        }

        // Set the 'langcode' and 'default_langcode' values as needed.
        if (!$original->isTranslatable() && $entity_type->isTranslatable()) {
          if ($entity->get($langcode_key)->isEmpty()) {
            $entity->set($langcode_key, \Drupal::languageManager()->getDefaultLanguage()->getId());
          }

          $entity->set($default_langcode_key, TRUE);
        }

        // Set the 'revision_translation_affected' field to TRUE to match the
        // return value of the case when the field does not exist.
        if ((!$original->isRevisionable() || !$original->isTranslatable()) && $entity_type->isRevisionable() && $entity_type->isTranslatable()) {
          $entity->set($revision_translation_affected_key, TRUE);
        }

        // Finally, save the entity in the temporary storage.
        $temporary_storage->restore($entity);
      }
      catch (\Exception $e) {
        $this->handleEntityTypeSchemaUpdateExceptionOnDataCopy($entity_type, $original, $sandbox);

        // Re-throw the original exception with a helpful message.
        $error_revision_id = $load_revisions ? ", revision ID: {$entity->getLoadedRevisionId()}" : '';
        throw new EntityStorageException("The entity update process failed while processing the entity type {$entity_type->id()}, ID: {$entity->id()}$error_revision_id.", $e->getCode(), $e);
      }

      $sandbox['progress']++;
      $sandbox['current_id'] = $identifier;
    }

    // Reset the cache in order to free memory as we progress.
    \Drupal::service('entity.memory_cache')->deleteAll();

    // Get an updated count of entities that still need to migrated to the new
    // storage.
    $missing = $this->database->select($table_name, 't')
      ->condition("t.$identifier_field", $sandbox['current_id'], '>')
      ->orderBy($identifier_field, 'ASC')
      ->countQuery()
      ->execute()
      ->fetchField();
    $sandbox['#finished'] = $missing ? $sandbox['progress'] / ($sandbox['progress'] + (int) $missing) : 1;
  }

  /**
   * Handles the case when an error occurs during the data copying step.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The updated entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The original entity type definition.
   * @param array &$sandbox
   *   The sandbox array from a hook_update_N() implementation.
   */
  protected function handleEntityTypeSchemaUpdateExceptionOnDataCopy(EntityTypeInterface $entity_type, EntityTypeInterface $original, array &$sandbox) {
  }

}
