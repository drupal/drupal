<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a schema converter for entity types with existing data.
 *
 * For now, this can only be used to convert an entity type from
 * non-revisionable to revisionable, however, it should be expanded so it can
 * also handle converting an entity type to be translatable.
 */
class SqlContentEntityStorageSchemaConverter {

  /**
   * The entity type ID this schema converter is responsible for.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity definition update manager service.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The last installed schema repository service.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $lastInstalledSchemaRepository;

  /**
   * The key-value collection for tracking installed storage schema.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $installedStorageSchema;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * SqlContentEntityStorageSchemaConverter constructor.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   Entity definition update manager service.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository
   *   Last installed schema repository service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct($entity_type_id, EntityTypeManagerInterface $entity_type_manager, EntityDefinitionUpdateManagerInterface $entity_definition_update_manager, EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository, KeyValueStoreInterface $installed_storage_schema, Connection $database) {
    $this->entityTypeId = $entity_type_id;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->lastInstalledSchemaRepository = $last_installed_schema_repository;
    $this->installedStorageSchema = $installed_storage_schema;
    $this->database = $database;
  }

  /**
   * Converts an entity type with existing data to be revisionable.
   *
   * This process does the following tasks:
   *   - creates the schema from scratch with the new revisionable entity type
   *     definition (i.e. the current definition of the entity type from code)
   *     using temporary table names;
   *   - loads the initial entity data by using the last installed entity and
   *     field storage definitions;
   *   - saves the entity data to the temporary tables;
   *   - at the end of the process:
   *     - deletes the original tables and replaces them with the temporary ones
   *       that hold the new (revisionable) entity data;
   *     - updates the installed entity schema data;
   *     - updates the entity type definition in order to trigger the
   *       \Drupal\Core\Entity\EntityTypeEvents::UPDATE event;
   *     - updates the field storage definitions in order to mark the
   *       revisionable ones as such.
   *
   * In case of an error during the entity save process, the temporary tables
   * are deleted and the original entity type and field storage definitions are
   * restored.
   *
   * @param array $sandbox
   *   The sandbox array from a hook_update_N() implementation.
   * @param string[] $fields_to_update
   *   (optional) An array of field names that should be converted to be
   *   revisionable. Note that the 'langcode' field, if present, is updated
   *   automatically. Defaults to an empty array.
   *
   * @throws \Exception
   *   Re-throws any exception raised during the update process.
   */
  public function convertToRevisionable(array &$sandbox, array $fields_to_update = []) {
    // If 'progress' is not set, then this will be the first run of the batch.
    if (!isset($sandbox['progress'])) {
      // Store the original entity type and field definitions in the $sandbox
      // array so we can use them later in the update process.
      $this->collectOriginalDefinitions($sandbox);

      // Create a temporary environment in which the new data will be stored.
      $this->createTemporaryDefinitions($sandbox, $fields_to_update);

      // Create the updated entity schema using temporary tables.
      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
      $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
      $storage->setTemporary(TRUE);
      $storage->setEntityType($sandbox['temporary_entity_type']);
      $storage->onEntityTypeCreate($sandbox['temporary_entity_type']);
    }

    // Copy over the existing data to the new temporary tables.
    $this->copyData($sandbox);

    // If the data copying has finished successfully, we can drop the temporary
    // tables and call the appropriate update mechanisms.
    if ($sandbox['#finished'] == 1) {
      $this->entityTypeManager->useCaches(FALSE);
      $actual_entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

      // Rename the original tables so we can put them back in place in case
      // anything goes wrong.
      foreach ($sandbox['original_table_mapping']->getTableNames() as $table_name) {
        $old_table_name = TemporaryTableMapping::getTempTableName($table_name, 'old_');
        $this->database->schema()->renameTable($table_name, $old_table_name);
      }

      // Put the new tables in place and update the entity type and field
      // storage definitions.
      try {
        $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
        $storage->setEntityType($actual_entity_type);
        $storage->setTemporary(FALSE);
        $actual_table_names = $storage->getTableMapping()->getTableNames();

        $table_name_mapping = [];
        foreach ($actual_table_names as $new_table_name) {
          $temp_table_name = TemporaryTableMapping::getTempTableName($new_table_name);
          $table_name_mapping[$temp_table_name] = $new_table_name;
          $this->database->schema()->renameTable($temp_table_name, $new_table_name);
        }

        // Rename the tables in the cached entity schema data.
        $entity_schema_data = $this->installedStorageSchema->get($this->entityTypeId . '.entity_schema_data', []);
        foreach ($entity_schema_data as $temp_table_name => $schema) {
          if (isset($table_name_mapping[$temp_table_name])) {
            $entity_schema_data[$table_name_mapping[$temp_table_name]] = $schema;
            unset($entity_schema_data[$temp_table_name]);
          }
        }
        $this->installedStorageSchema->set($this->entityTypeId . '.entity_schema_data', $entity_schema_data);

        // Rename the tables in the cached field schema data.
        foreach ($sandbox['updated_storage_definitions'] as $storage_definition) {
          $field_schema_data = $this->installedStorageSchema->get($this->entityTypeId . '.field_schema_data.' . $storage_definition->getName(), []);
          foreach ($field_schema_data as $temp_table_name => $schema) {
            if (isset($table_name_mapping[$temp_table_name])) {
              $field_schema_data[$table_name_mapping[$temp_table_name]] = $schema;
              unset($field_schema_data[$temp_table_name]);
            }
          }
          $this->installedStorageSchema->set($this->entityTypeId . '.field_schema_data.' . $storage_definition->getName(), $field_schema_data);
        }

        // Instruct the entity schema handler that data migration has been
        // handled already and update the entity type.
        $actual_entity_type->set('requires_data_migration', FALSE);
        $this->entityDefinitionUpdateManager->updateEntityType($actual_entity_type);

        // Update the field storage definitions.
        $this->updateFieldStorageDefinitionsToRevisionable($actual_entity_type, $sandbox['original_storage_definitions'], $fields_to_update);
      }
      catch (\Exception $e) {
        // Something went wrong, bring back the original tables.
        foreach ($sandbox['original_table_mapping']->getTableNames() as $table_name) {
          // We are in the 'original data recovery' phase, so we need to be sure
          // that the initial tables can be properly restored.
          if ($this->database->schema()->tableExists($table_name)) {
            $this->database->schema()->dropTable($table_name);
          }

          $old_table_name = TemporaryTableMapping::getTempTableName($table_name, 'old_');
          $this->database->schema()->renameTable($old_table_name, $table_name);
        }

        // Re-throw the original exception.
        throw $e;
      }

      // At this point the update process either finished successfully or any
      // error has been handled already, so we can drop the backup entity
      // tables.
      foreach ($sandbox['original_table_mapping']->getTableNames() as $table_name) {
        $old_table_name = TemporaryTableMapping::getTempTableName($table_name, 'old_');
        $this->database->schema()->dropTable($old_table_name);
      }
    }
  }

  /**
   * Loads entities from the original storage and saves them to a temporary one.
   *
   * @param array &$sandbox
   *   The sandbox array from a hook_update_N() implementation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown in case of an error during the entity save process.
   */
  protected function copyData(array &$sandbox) {
    /** @var \Drupal\Core\Entity\Sql\TemporaryTableMapping $temporary_table_mapping */
    $temporary_table_mapping = $sandbox['temporary_table_mapping'];
    $temporary_entity_type = $sandbox['temporary_entity_type'];
    $original_table_mapping = $sandbox['original_table_mapping'];
    $original_entity_type = $sandbox['original_entity_type'];

    $original_base_table = $original_entity_type->getBaseTable();

    $revision_id_key = $temporary_entity_type->getKey('revision');
    $revision_default_key = $temporary_entity_type->getRevisionMetadataKey('revision_default');
    $revision_translation_affected_key = $temporary_entity_type->getKey('revision_translation_affected');

    // If 'progress' is not set, then this will be the first run of the batch.
    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['current_id'] = 0;
      $sandbox['max'] = $this->database->select($original_base_table)
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    $id = $original_entity_type->getKey('id');

    // Define the step size.
    $step_size = Settings::get('entity_update_batch_size', 50);

    // Get the next entity IDs to migrate.
    $entity_ids = $this->database->select($original_base_table)
      ->fields($original_base_table, [$id])
      ->condition($id, $sandbox['current_id'], '>')
      ->orderBy($id, 'ASC')
      ->range(0, $step_size)
      ->execute()
      ->fetchAllKeyed(0, 0);

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
    $storage = $this->entityTypeManager->getStorage($temporary_entity_type->id());
    $storage->setEntityType($original_entity_type);
    $storage->setTableMapping($original_table_mapping);

    $entities = $storage->loadMultiple($entity_ids);

    // Now inject the temporary entity type definition and table mapping in the
    // storage and re-save the entities.
    $storage->setEntityType($temporary_entity_type);
    $storage->setTableMapping($temporary_table_mapping);

    foreach ($entities as $entity_id => $entity) {
      try {
        // Set the revision ID to be same as the entity ID.
        $entity->set($revision_id_key, $entity_id);

        // We had no revisions so far, so the existing data belongs to the
        // default revision now.
        $entity->set($revision_default_key, TRUE);

        // Set the 'revision_translation_affected' flag to TRUE to match the
        // previous API return value: if the field was not defined the value
        // returned was always TRUE.
        $entity->set($revision_translation_affected_key, TRUE);

        // Treat the entity as new in order to make the storage do an INSERT
        // rather than an UPDATE.
        $entity->enforceIsNew(TRUE);

        // Finally, save the entity in the temporary storage.
        $storage->save($entity);
      }
      catch (\Exception $e) {
        // In case of an error during the save process, we need to roll back the
        // original entity type and field storage definitions and clean up the
        // temporary tables.
        $this->restoreOriginalDefinitions($sandbox);

        foreach ($temporary_table_mapping->getTableNames() as $table_name) {
          $this->database->schema()->dropTable($table_name);
        }

        // Re-throw the original exception with a helpful message.
        throw new EntityStorageException("The entity update process failed while processing the entity {$original_entity_type->id()}:$entity_id.", $e->getCode(), $e);
      }

      $sandbox['progress']++;
      $sandbox['current_id'] = $entity_id;
    }

    // If we're not in maintenance mode, the number of entities could change at
    // any time so make sure that we always use the latest record count.
    $sandbox['max'] = $this->database->select($original_base_table)
      ->countQuery()
      ->execute()
      ->fetchField();

    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  }

  /**
   * Updates field definitions to be revisionable.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   A content entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   An array of field storage definitions.
   * @param array $fields_to_update
   *   (optional) An array of field names for which to enable revision support.
   *   Defaults to an empty array.
   * @param bool $update_cached_definitions
   *   (optional) Whether to update the cached field storage definitions in the
   *   entity definition update manager. Defaults to TRUE.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   An array of updated field storage definitions.
   */
  protected function updateFieldStorageDefinitionsToRevisionable(ContentEntityTypeInterface $entity_type, array $storage_definitions, array $fields_to_update = [], $update_cached_definitions = TRUE) {
    $updated_storage_definitions = array_map(function ($storage_definition) {
      return clone $storage_definition;
    }, $storage_definitions);

    // Update the 'langcode' field manually, as it is configured in the base
    // content entity field definitions.
    if ($entity_type->hasKey('langcode')) {
      $fields_to_update = array_merge([$entity_type->getKey('langcode')], $fields_to_update);
    }

    foreach ($fields_to_update as $field_name) {
      if (!$updated_storage_definitions[$field_name]->isRevisionable()) {
        $updated_storage_definitions[$field_name]->setRevisionable(TRUE);

        if ($update_cached_definitions) {
          $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($updated_storage_definitions[$field_name]);
        }
      }
    }

    // Add the revision ID field.
    $revision_field = BaseFieldDefinition::create('integer')
      ->setName($entity_type->getKey('revision'))
      ->setTargetEntityTypeId($entity_type->id())
      ->setTargetBundle(NULL)
      ->setLabel(new TranslatableMarkup('Revision ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    if ($update_cached_definitions) {
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition($revision_field->getName(), $entity_type->id(), $entity_type->getProvider(), $revision_field);
    }
    $updated_storage_definitions[$entity_type->getKey('revision')] = $revision_field;

    // Add the default revision flag field.
    $field_name = $entity_type->getRevisionMetadataKey('revision_default');
    $storage_definition = BaseFieldDefinition::create('boolean')
      ->setName($field_name)
      ->setTargetEntityTypeId($entity_type->id())
      ->setTargetBundle(NULL)
      ->setLabel(t('Default revision'))
      ->setDescription(t('A flag indicating whether this was a default revision when it was saved.'))
      ->setStorageRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE);

    if ($update_cached_definitions) {
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition($field_name, $entity_type->id(), $entity_type->getProvider(), $storage_definition);
    }
    $updated_storage_definitions[$field_name] = $storage_definition;

    // Add the 'revision_translation_affected' field if needed.
    if ($entity_type->isTranslatable()) {
      $revision_translation_affected_field = BaseFieldDefinition::create('boolean')
        ->setName($entity_type->getKey('revision_translation_affected'))
        ->setTargetEntityTypeId($entity_type->id())
        ->setTargetBundle(NULL)
        ->setLabel(new TranslatableMarkup('Revision translation affected'))
        ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
        ->setReadOnly(TRUE)
        ->setRevisionable(TRUE)
        ->setTranslatable(TRUE);

      if ($update_cached_definitions) {
        $this->entityDefinitionUpdateManager->installFieldStorageDefinition($revision_translation_affected_field->getName(), $entity_type->id(), $entity_type->getProvider(), $revision_translation_affected_field);
      }
      $updated_storage_definitions[$entity_type->getKey('revision_translation_affected')] = $revision_translation_affected_field;
    }

    return $updated_storage_definitions;
  }

  /**
   * Collects the original definitions of an entity type and its fields.
   *
   * @param array &$sandbox
   *   A sandbox array from a hook_update_N() implementation.
   */
  protected function collectOriginalDefinitions(array &$sandbox) {
    $original_entity_type = $this->lastInstalledSchemaRepository->getLastInstalledDefinition($this->entityTypeId);
    $original_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($this->entityTypeId);

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
    $storage->setEntityType($original_entity_type);
    $original_table_mapping = $storage->getTableMapping($original_storage_definitions);

    $sandbox['original_entity_type'] = $original_entity_type;
    $sandbox['original_storage_definitions'] = $original_storage_definitions;
    $sandbox['original_table_mapping'] = $original_table_mapping;

    $sandbox['original_entity_schema_data'] = $this->installedStorageSchema->get($this->entityTypeId . '.entity_schema_data', []);
    foreach ($original_storage_definitions as $storage_definition) {
      $sandbox['original_field_schema_data'][$storage_definition->getName()] = $this->installedStorageSchema->get($this->entityTypeId . '.field_schema_data.' . $storage_definition->getName(), []);
    }
  }

  /**
   * Restores the entity type, field storage definitions and their schema data.
   *
   * @param array $sandbox
   *   The sandbox array from a hook_update_N() implementation.
   */
  protected function restoreOriginalDefinitions(array $sandbox) {
    $original_entity_type = $sandbox['original_entity_type'];
    $original_storage_definitions = $sandbox['original_storage_definitions'];
    $original_entity_schema_data = $sandbox['original_entity_schema_data'];
    $original_field_schema_data = $sandbox['original_field_schema_data'];

    $this->lastInstalledSchemaRepository->setLastInstalledDefinition($original_entity_type);
    $this->lastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($original_entity_type->id(), $original_storage_definitions);

    $this->installedStorageSchema->set($original_entity_type->id() . '.entity_schema_data', $original_entity_schema_data);
    foreach ($original_field_schema_data as $field_name => $field_schema_data) {
      $this->installedStorageSchema->set($original_entity_type->id() . '.field_schema_data.' . $field_name, $field_schema_data);
    }
  }

  /**
   * Creates temporary entity type, field storage and table mapping objects.
   *
   * @param array &$sandbox
   *   A sandbox array from a hook_update_N() implementation.
   * @param string[] $fields_to_update
   *   (optional) An array of field names that should be converted to be
   *   revisionable. Note that the 'langcode' field, if present, is updated
   *   automatically. Defaults to an empty array.
   */
  protected function createTemporaryDefinitions(array &$sandbox, array $fields_to_update) {
    // Make sure to get the latest entity type definition from code.
    $this->entityTypeManager->useCaches(FALSE);
    $actual_entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    $temporary_entity_type = clone $actual_entity_type;
    $temporary_entity_type->set('base_table', TemporaryTableMapping::getTempTableName($temporary_entity_type->getBaseTable()));
    $temporary_entity_type->set('revision_table', TemporaryTableMapping::getTempTableName($temporary_entity_type->getRevisionTable()));
    if ($temporary_entity_type->isTranslatable()) {
      $temporary_entity_type->set('data_table', TemporaryTableMapping::getTempTableName($temporary_entity_type->getDataTable()));
      $temporary_entity_type->set('revision_data_table', TemporaryTableMapping::getTempTableName($temporary_entity_type->getRevisionDataTable()));
    }

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
    $storage->setTemporary(TRUE);
    $storage->setEntityType($temporary_entity_type);

    $updated_storage_definitions = $this->updateFieldStorageDefinitionsToRevisionable($temporary_entity_type, $sandbox['original_storage_definitions'], $fields_to_update, FALSE);
    $temporary_table_mapping = $storage->getTableMapping($updated_storage_definitions);

    $sandbox['temporary_entity_type'] = $temporary_entity_type;
    $sandbox['temporary_table_mapping'] = $temporary_table_mapping;
    $sandbox['updated_storage_definitions'] = $updated_storage_definitions;
  }

}
