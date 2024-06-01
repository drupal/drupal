<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;

/**
 * Defines a schema handler that supports revisionable, translatable entities.
 *
 * Entity types may extend this class and optimize the generated schema for all
 * entity base tables by overriding getEntitySchema() for cross-field
 * optimizations and getSharedTableFieldSchema() for optimizations applying to
 * a single field.
 */
class SqlContentEntityStorageSchema implements DynamicallyFieldableEntityStorageSchemaInterface {

  use DependencySerializationTrait;
  use SqlFieldableEntityTypeListenerTrait {
    onFieldableEntityTypeUpdate as traitOnFieldableEntityTypeUpdate;
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type this schema builder is responsible for.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $entityType;

  /**
   * The storage field definitions for this entity type.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected $fieldStorageDefinitions;

  /**
   * The storage object for the given entity type.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $storage;

  /**
   * A static cache of the generated schema array.
   *
   * @var array
   */
  protected $schema;

  /**
   * The database connection to be used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The key-value collection for tracking installed storage schema.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $installedStorageSchema;

  /**
   * The key-value collection for tracking entity update backup repository.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $updateBackupRepository;

  /**
   * The deleted fields repository.
   *
   * @var \Drupal\Core\Field\DeletedFieldsRepositoryInterface
   */
  protected $deletedFieldsRepository;

  /**
   * Constructs a SqlContentEntityStorageSchema.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage
   *   The storage of the entity type. This must be an SQL-based storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentEntityTypeInterface $entity_type, SqlContentEntityStorage $storage, Connection $database, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->storage = clone $storage;
    $this->database = $database;
    $this->entityFieldManager = $entity_field_manager;

    $this->entityType = $entity_type_manager->getActiveDefinition($entity_type->id());
    $this->fieldStorageDefinitions = $entity_field_manager->getActiveFieldStorageDefinitions($entity_type->id());
  }

  /**
   * Gets the keyvalue collection for tracking the installed schema.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *
   * @todo Inject this dependency in the constructor once this class can be
   *   instantiated as a regular entity handler:
   *   https://www.drupal.org/node/2332857.
   */
  protected function installedStorageSchema() {
    if (!isset($this->installedStorageSchema)) {
      $this->installedStorageSchema = \Drupal::keyValue('entity.storage_schema.sql');
    }
    return $this->installedStorageSchema;
  }

  /**
   * Gets the deleted fields repository.
   *
   * @return \Drupal\Core\Field\DeletedFieldsRepositoryInterface
   *   The deleted fields repository.
   *
   * @todo Inject this dependency in the constructor once this class can be
   *   instantiated as a regular entity handler:
   *   https://www.drupal.org/node/2332857.
   */
  protected function deletedFieldsRepository() {
    if (!isset($this->deletedFieldsRepository)) {
      $this->deletedFieldsRepository = \Drupal::service('entity_field.deleted_fields_repository');
    }
    return $this->deletedFieldsRepository;
  }

  /**
   * Gets the key/value collection for tracking the entity update backups.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   A key/value collection.
   *
   * @todo Inject this dependency in the constructor once this class can be
   *   instantiated as a regular entity handler.
   *   @see https://www.drupal.org/node/2332857
   */
  protected function updateBackupRepository() {
    if (!isset($this->updateBackupRepository)) {
      $this->updateBackupRepository = \Drupal::keyValue('entity.update_backup');
    }
    return $this->updateBackupRepository;
  }

  /**
   * Refreshes the table mapping with updated definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[]|null $storage_definitions
   *   (optional) An array of field storage definitions. Defaults to the last
   *   installed field storage definition.
   *
   * @return \Drupal\Core\Entity\Sql\DefaultTableMapping
   *   A table mapping object.
   */
  protected function getTableMapping(EntityTypeInterface $entity_type, ?array $storage_definitions = NULL) {
    // Allow passing a single field storage definition when updating a field.
    if ($storage_definitions && count($storage_definitions) === 1) {
      $storage_definition = reset($storage_definitions);
      $field_storage_definitions = [$storage_definition->getName() => $storage_definition] + $this->fieldStorageDefinitions;
    }
    else {
      $field_storage_definitions = $storage_definitions ?: $this->fieldStorageDefinitions;
    }

    return $this->storage->getCustomTableMapping($entity_type, $field_storage_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return $this->hasSharedTableStructureChange($entity_type, $original) ||
      // Detect changes in key or index definitions.
      $this->getEntitySchemaData($entity_type, $this->getEntitySchema($entity_type, TRUE)) != $this->loadEntitySchemaData($original);
  }

  /**
   * Detects whether there is a change in the shared table structure.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The new entity type.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The origin entity type.
   *
   * @return bool
   *   Returns TRUE if either the revisionable or translatable flag changes or
   *   a table has been renamed.
   */
  protected function hasSharedTableStructureChange(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return $entity_type->isRevisionable() != $original->isRevisionable() ||
      $entity_type->isTranslatable() != $original->isTranslatable() ||
      $this->hasSharedTableNameChanges($entity_type, $original);
  }

  /**
   * Detects whether any table name got renamed in an entity type update.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The new entity type.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The origin entity type.
   *
   * @return bool
   *   Returns TRUE if there have been changes.
   */
  protected function hasSharedTableNameChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $base_table = $this->database->schema()->tableExists($entity_type->getBaseTable());
    $data_table = $this->database->schema()->tableExists($entity_type->getDataTable());
    $revision_table = $this->database->schema()->tableExists($entity_type->getRevisionTable());
    $revision_data_table = $this->database->schema()->tableExists($entity_type->getRevisionDataTable());

    // We first check if the new table already exists because the storage might
    // have created it even though it wasn't specified in the entity type
    // definition.
    return (!$base_table && $entity_type->getBaseTable() != $original->getBaseTable()) ||
      (!$data_table && $entity_type->getDataTable() != $original->getDataTable()) ||
      (!$revision_table && $entity_type->getRevisionTable() != $original->getRevisionTable()) ||
      (!$revision_data_table && $entity_type->getRevisionDataTable() != $original->getRevisionDataTable());
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $table_mapping = $this->getTableMapping($this->entityType);

    if (
      $storage_definition->hasCustomStorage() != $original->hasCustomStorage() ||
      $storage_definition->getSchema() != $original->getSchema() ||
      $storage_definition->isRevisionable() != $original->isRevisionable() ||
      $table_mapping->allowsSharedTableStorage($storage_definition) != $table_mapping->allowsSharedTableStorage($original) ||
      $table_mapping->requiresDedicatedTableStorage($storage_definition) != $table_mapping->requiresDedicatedTableStorage($original)
    ) {
      return TRUE;
    }

    if ($storage_definition->hasCustomStorage()) {
      // The field has custom storage, so we don't know if a schema change is
      // needed or not, but since per the initial checks earlier in this
      // function, nothing about the definition changed that we manage, we
      // return FALSE.
      return FALSE;
    }

    $current_schema = $this->getSchemaFromStorageDefinition($storage_definition);
    $this->processFieldStorageSchema($current_schema);
    $installed_schema = $this->loadFieldSchemaData($original);
    $this->processFieldStorageSchema($installed_schema);

    return $current_schema != $installed_schema;
  }

  /**
   * Gets the schema data for the given field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition. The field that must not have custom
   *   storage, i.e. the storage must take care of storing the field.
   *
   * @return array
   *   The schema data.
   */
  protected function getSchemaFromStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    assert(!$storage_definition->hasCustomStorage());
    $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
    $schema = [];
    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $schema = $this->getDedicatedTableSchema($storage_definition);
    }
    elseif ($table_mapping->allowsSharedTableStorage($storage_definition)) {
      $field_name = $storage_definition->getName();
      foreach (array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames()) as $table_name) {
        if (in_array($field_name, $table_mapping->getFieldNames($table_name))) {
          $column_names = $table_mapping->getColumnNames($storage_definition->getName());
          $schema[$table_name] = $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names);
        }
      }
    }
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // If the original storage has existing entities, or it is impossible to
    // determine if that is the case, require entity data to be migrated.
    $original_storage_class = $original->getStorageClass();
    if (!class_exists($original_storage_class)) {
      return TRUE;
    }

    // Data migration is not needed when only indexes changed, as they can be
    // applied if there is data.
    if (!$this->hasSharedTableStructureChange($entity_type, $original)) {
      return FALSE;
    }

    return $this->storage->hasData();
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldDataMigration(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return !$this->storage->countFieldData($original, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $this->onFieldableEntityTypeCreate($entity_type, $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $this->checkEntityType($entity_type);
    $this->checkEntityType($original);

    // If no schema changes are needed, we don't need to do anything.
    if (!$this->requiresEntityStorageSchemaChanges($entity_type, $original)) {
      return;
    }

    // If shared table schema changes are needed, we can't proceed.
    if (!class_exists($original->getStorageClass()) || $this->hasSharedTableStructureChange($entity_type, $original)) {
      throw new EntityStorageException('It is not possible to change the entity type schema outside of a batch context. Use EntityDefinitionUpdateManagerInterface::updateFieldableEntityType() instead.');
    }

    // Drop original indexes and unique keys.
    $this->deleteEntitySchemaIndexes($this->loadEntitySchemaData($entity_type));

    // Create new indexes and unique keys.
    $entity_schema = $this->getEntitySchema($entity_type, TRUE);
    $this->createEntitySchemaIndexes($entity_schema);

    // Store the updated entity schema.
    $this->saveEntitySchemaData($entity_type, $entity_schema);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->checkEntityType($entity_type);
    $schema_handler = $this->database->schema();

    // Delete entity and field tables.
    $table_names = $this->getTableNames($entity_type, $this->fieldStorageDefinitions, $this->getTableMapping($entity_type));
    foreach ($table_names as $table_name) {
      if ($schema_handler->tableExists($table_name)) {
        $schema_handler->dropTable($table_name);
      }
    }

    // Delete the field schema data.
    foreach ($this->fieldStorageDefinitions as $field_storage_definition) {
      $this->deleteFieldSchemaData($field_storage_definition);
    }

    // Delete the entity schema.
    $this->deleteEntitySchemaData($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeCreate(EntityTypeInterface $entity_type, array $field_storage_definitions) {
    // When installing a fieldable entity type, we have to use the provided
    // entity type and field storage definitions.
    $this->entityType = $entity_type;
    $this->fieldStorageDefinitions = $field_storage_definitions;

    $this->checkEntityType($entity_type);
    $schema_handler = $this->database->schema();

    // Create entity tables.
    $schema = $this->getEntitySchema($entity_type, TRUE);
    foreach ($schema as $table_name => $table_schema) {
      if (!$schema_handler->tableExists($table_name)) {
        $schema_handler->createTable($table_name, $table_schema);
      }
    }

    // Create dedicated field tables.
    $table_mapping = $this->getTableMapping($this->entityType);
    foreach ($this->fieldStorageDefinitions as $field_storage_definition) {
      if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
        $this->createDedicatedTableSchema($field_storage_definition);
      }
      elseif ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
        // The shared tables are already fully created, but we need to save the
        // per-field schema definitions for later use.
        $this->createSharedTableSchema($field_storage_definition, TRUE);
      }
    }

    // Save data about entity indexes and keys.
    $this->saveEntitySchemaData($entity_type, $schema);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, ?array &$sandbox = NULL) {
    $this->traitOnFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
  }

  /**
   * {@inheritdoc}
   */
  protected function preUpdateEntityTypeSchema(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, ?array &$sandbox = NULL) {
    $temporary_prefix = static::getTemporaryTableMappingPrefix($entity_type, $field_storage_definitions);
    $sandbox['temporary_table_mapping'] = $this->storage->getCustomTableMapping($entity_type, $field_storage_definitions, $temporary_prefix);
    $sandbox['new_table_mapping'] = $this->storage->getCustomTableMapping($entity_type, $field_storage_definitions);
    $sandbox['original_table_mapping'] = $this->storage->getCustomTableMapping($original, $original_field_storage_definitions);

    $backup_prefix = static::getTemporaryTableMappingPrefix($original, $original_field_storage_definitions, 'old_');
    $sandbox['backup_table_mapping'] = $this->storage->getCustomTableMapping($original, $original_field_storage_definitions, $backup_prefix);
    $sandbox['backup_prefix_key'] = substr($backup_prefix, 4);
    $sandbox['backup_request_time'] = \Drupal::time()->getRequestTime();

    // Create temporary tables based on the new entity type and field storage
    // definitions.
    $temporary_table_names = array_combine(
      $this->getTableNames($entity_type, $field_storage_definitions, $sandbox['new_table_mapping']),
      $this->getTableNames($entity_type, $field_storage_definitions, $sandbox['temporary_table_mapping'])
    );
    $this->entityType = $entity_type;
    $this->fieldStorageDefinitions = $field_storage_definitions;

    // Update the storage's entity type and field storage definitions because
    // ::getEntitySchema() and ::getSharedTableFieldSchema() overrides are
    // retrieving table names from these definitions.
    $this->storage->setEntityType($entity_type);
    $this->storage->setFieldStorageDefinitions($field_storage_definitions);
    $this->storage->setTableMapping($sandbox['new_table_mapping']);

    $schema = $this->getEntitySchema($entity_type, TRUE);
    $sandbox['new_entity_schema'] = $schema;

    // Filter out tables which are not part of the table mapping.
    $schema = array_intersect_key($schema, $temporary_table_names);

    // Create entity tables.
    foreach ($schema as $table_name => $table_schema) {
      $this->database->schema()->createTable($temporary_table_names[$table_name], $table_schema);
    }

    // Create dedicated field tables.
    foreach ($field_storage_definitions as $field_storage_definition) {
      if ($sandbox['temporary_table_mapping']->requiresDedicatedTableStorage($field_storage_definition)) {
        $schema = $this->getDedicatedTableSchema($field_storage_definition, $entity_type);

        // Filter out tables which are not part of the table mapping.
        $schema = array_intersect_key($schema, $temporary_table_names);
        foreach ($schema as $table_name => $table_schema) {
          $this->database->schema()->createTable($temporary_table_names[$table_name], $table_schema);
        }
      }
    }

    // Restore the original definitions and table mapping so the data copying
    // step can load existing data properly.
    $this->storage->setEntityType($original);
    $this->storage->setFieldStorageDefinitions($original_field_storage_definitions);
    $this->storage->setTableMapping($sandbox['original_table_mapping']);

    // Store the temporary table name mappings for later reuse.
    $sandbox['temporary_table_names'] = $temporary_table_names;
  }

  /**
   * {@inheritdoc}
   */
  protected function postUpdateEntityTypeSchema(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, ?array &$sandbox = NULL) {
    /** @var \Drupal\Core\Entity\Sql\TableMappingInterface $original_table_mapping */
    $original_table_mapping = $sandbox['original_table_mapping'];
    /** @var \Drupal\Core\Entity\Sql\TableMappingInterface $new_table_mapping */
    $new_table_mapping = $sandbox['new_table_mapping'];
    /** @var \Drupal\Core\Entity\Sql\TableMappingInterface $backup_table_mapping */
    $backup_table_mapping = $sandbox['backup_table_mapping'];

    // Rename the original tables so we can put them back in place in case
    // anything goes wrong.
    $backup_table_names = array_combine(
      $this->getTableNames($original, $original_field_storage_definitions, $original_table_mapping),
      $this->getTableNames($original, $original_field_storage_definitions, $backup_table_mapping)
    );
    $renamed_tables = [];
    try {
      foreach ($backup_table_names as $original_table_name => $backup_table_name) {
        $this->database->schema()->renameTable($original_table_name, $backup_table_name);
        $renamed_tables[$original_table_name] = $backup_table_name;
      }
    }
    catch (\Exception $e) {
      foreach ($renamed_tables as $original_table_name => $backup_table_name) {
        $this->database->schema()->renameTable($backup_table_name, $original_table_name);
      }

      // Re-throw the original exception.
      throw $e;
    }

    // Put the new tables in place and update the entity type and field storage
    // definitions.
    try {
      foreach ($sandbox['temporary_table_names'] as $current_table_name => $temp_table_name) {
        $this->database->schema()->renameTable($temp_table_name, $current_table_name);
      }

      // Store the updated entity schema.
      $new_entity_schema = $sandbox['new_entity_schema'];
      $this->schema[$entity_type->id()] = $new_entity_schema;
      $this->entityType = $entity_type;
      $this->fieldStorageDefinitions = $field_storage_definitions;
      $this->saveEntitySchemaData($entity_type, $new_entity_schema);

      // The storage needs to use the updated definitions and table mapping
      // before generating and saving the final field schema data.
      $this->storage->setEntityType($entity_type);
      $this->storage->setFieldStorageDefinitions($field_storage_definitions);
      $this->storage->setTableMapping($new_table_mapping);

      // Store the updated field schema for each field storage.
      foreach ($field_storage_definitions as $field_storage_definition) {
        if ($new_table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
          $this->createDedicatedTableSchema($field_storage_definition, TRUE);
        }
        elseif ($new_table_mapping->allowsSharedTableStorage($field_storage_definition)) {
          // The shared tables are already fully created, but we need to save
          // the per-field schema definitions for later use.
          $this->createSharedTableSchema($field_storage_definition, TRUE);
        }
      }
    }
    catch (\Exception $e) {
      // Something went wrong, bring back the original tables.
      foreach ($backup_table_names as $original_table_name => $backup_table_name) {
        // We are in the 'original data recovery' phase, so we need to be sure
        // that the initial tables can be properly restored.
        if ($this->database->schema()->tableExists($original_table_name)) {
          $this->database->schema()->dropTable($original_table_name);
        }

        $this->database->schema()->renameTable($backup_table_name, $original_table_name);
      }

      // Re-throw the original exception.
      throw $e;
    }

    // At this point the update process either finished successfully or any
    // error has been thrown already. We can either keep the backup tables in
    // place or drop them.
    if (Settings::get('entity_update_backup', TRUE)) {
      $backup_key = $sandbox['backup_prefix_key'];
      $backup = [
        'entity_type' => $original,
        'field_storage_definitions' => $original_field_storage_definitions,
        'table_mapping' => $backup_table_mapping,
        'request_time' => $sandbox['backup_request_time'],
      ];
      $this->updateBackupRepository()->set("{$original->id()}.$backup_key", $backup);
    }
    else {
      foreach ($backup_table_names as $original_table_name => $backup_table_name) {
        $this->database->schema()->dropTable($backup_table_name);
      }
    }
  }

  /**
   * Gets a list of table names for this entity type, field storage and mapping.
   *
   * The default table mapping does not return dedicated revision table names
   * for non-revisionable fields attached to revisionable entity types. Since
   * both the storage and the storage handlers expect them to be existing, the
   * missing table names need to be manually restored.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storage_definitions
   *   An array of field storage definitions.
   * @param \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping
   *   A table mapping.
   *
   * @return string[]
   *   An array of field table names.
   *
   * @todo Remove this once the behavior of the default table mapping, the
   *    storage handler, and the storage schema handler are reconciled in
   *    https://www.drupal.org/node/3113639.
   */
  private function getTableNames(EntityTypeInterface $entity_type, array $field_storage_definitions, TableMappingInterface $table_mapping) {
    $table_names = $table_mapping->getTableNames();
    if ($table_mapping instanceof DefaultTableMapping && $entity_type->isRevisionable()) {
      foreach ($field_storage_definitions as $storage_definition) {
        if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
          $dedicated_revision_table_name = $table_mapping->getDedicatedRevisionTableName($storage_definition);
          if (!$storage_definition->isRevisionable() && !in_array($dedicated_revision_table_name, $table_names)) {
            $table_names[] = $dedicated_revision_table_name;
          }
        }
      }
    }
    return $table_names;
  }

  /**
   * {@inheritdoc}
   */
  protected function handleEntityTypeSchemaUpdateExceptionOnDataCopy(EntityTypeInterface $entity_type, EntityTypeInterface $original, array &$sandbox) {
    // In case of an error during the save process, we need to clean up the
    // temporary tables.
    foreach ($sandbox['temporary_table_names'] as $table_name) {
      $this->database->schema()->dropTable($table_name);
    }
  }

  /**
   * Gets a string to be used as a prefix for a temporary table mapping object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storage_definitions
   *   An array of field storage definitions.
   * @param string $first_prefix_part
   *   (optional) The first part of the prefix. Defaults to 'tmp_'.
   *
   * @return string
   *   A temporary table mapping prefix.
   *
   * @internal
   */
  public static function getTemporaryTableMappingPrefix(EntityTypeInterface $entity_type, array $field_storage_definitions, $first_prefix_part = 'tmp_') {
    // Construct a unique prefix based on the contents of the entity type and
    // field storage definitions.
    $prefix_parts[] = spl_object_hash($entity_type);
    foreach ($field_storage_definitions as $storage_definition) {
      $prefix_parts[] = spl_object_hash($storage_definition);
    }
    $prefix_parts[] = \Drupal::time()->getRequestTime();
    $hash = hash('sha256', implode('', $prefix_parts));

    return $first_prefix_part . substr($hash, 0, 6);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $this->fieldStorageDefinitions[$storage_definition->getName()] = $storage_definition;
    $this->performFieldSchemaOperation('create', $storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $this->fieldStorageDefinitions[$storage_definition->getName()] = $storage_definition;
    $this->performFieldSchemaOperation('update', $storage_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    // If the field storage does not have any data, we can safely delete its
    // schema.
    if (!$this->storage->countFieldData($storage_definition, TRUE)) {
      $this->performFieldSchemaOperation('delete', $storage_definition);
      return;
    }

    // There's nothing else we can do if the field storage has a custom storage.
    if ($storage_definition->hasCustomStorage()) {
      return;
    }

    $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
    $field_table_name = $table_mapping->getFieldTableName($storage_definition->getName());

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      // Move the table to a unique name while the table contents are being
      // deleted.
      $table = $table_mapping->getDedicatedDataTableName($storage_definition);
      $new_table = $table_mapping->getDedicatedDataTableName($storage_definition, TRUE);
      $this->database->schema()->renameTable($table, $new_table);
      if ($this->entityType->isRevisionable()) {
        $revision_table = $table_mapping->getDedicatedRevisionTableName($storage_definition);
        $revision_new_table = $table_mapping->getDedicatedRevisionTableName($storage_definition, TRUE);
        $this->database->schema()->renameTable($revision_table, $revision_new_table);
      }
    }
    else {
      // Move the field data from the shared table to a dedicated one in order
      // to allow it to be purged like any other field.
      $shared_table_field_columns = $table_mapping->getColumnNames($storage_definition->getName());

      // Refresh the table mapping to use the deleted storage definition.
      $deleted_storage_definition = $this->deletedFieldsRepository()->getFieldStorageDefinitions()[$storage_definition->getUniqueStorageIdentifier()];
      $table_mapping = $this->getTableMapping($this->entityType, [$deleted_storage_definition]);

      $dedicated_table_field_schema = $this->getDedicatedTableSchema($deleted_storage_definition);
      $dedicated_table_field_columns = $table_mapping->getColumnNames($deleted_storage_definition->getName());

      $dedicated_table_name = $table_mapping->getDedicatedDataTableName($deleted_storage_definition, TRUE);
      $dedicated_table_name_mapping[$table_mapping->getDedicatedDataTableName($deleted_storage_definition)] = $dedicated_table_name;
      if ($this->entityType->isRevisionable()) {
        $dedicated_revision_table_name = $table_mapping->getDedicatedRevisionTableName($deleted_storage_definition, TRUE);
        $dedicated_table_name_mapping[$table_mapping->getDedicatedRevisionTableName($deleted_storage_definition)] = $dedicated_revision_table_name;
      }

      // Create the dedicated field tables using "deleted" table names.
      foreach ($dedicated_table_field_schema as $name => $table) {
        if (!$this->database->schema()->tableExists($dedicated_table_name_mapping[$name])) {
          $this->database->schema()->createTable($dedicated_table_name_mapping[$name], $table);
        }
        else {
          throw new EntityStorageException('The field ' . $storage_definition->getName() . ' has already been deleted and it is in the process of being purged.');
        }
      }

      try {
        if ($this->database->supportsTransactionalDDL()) {
          // If the database supports transactional DDL, we can go ahead and rely
          // on it. If not, we will have to rollback manually if something fails.
          $transaction = $this->database->startTransaction();
        }

        // Copy the data from the base table.
        $this->database->insert($dedicated_table_name)
          ->from($this->getSelectQueryForFieldStorageDeletion($field_table_name, $shared_table_field_columns, $dedicated_table_field_columns))
          ->execute();

        // Copy the data from the revision table.
        if (isset($dedicated_revision_table_name)) {
          if ($this->entityType->isTranslatable()) {
            $revision_table = $storage_definition->isRevisionable() ? $this->storage->getRevisionDataTable() : $this->storage->getDataTable();
          }
          else {
            $revision_table = $storage_definition->isRevisionable() ? $this->storage->getRevisionTable() : $this->storage->getBaseTable();
          }
          $this->database->insert($dedicated_revision_table_name)
            ->from($this->getSelectQueryForFieldStorageDeletion($revision_table, $shared_table_field_columns, $dedicated_table_field_columns, $field_table_name))
            ->execute();
        }
      }
      catch (\Exception $e) {
        if ($this->database->supportsTransactionalDDL()) {
          if (isset($transaction)) {
            $transaction->rollBack();
          }
        }
        else {
          // Delete the dedicated tables.
          foreach ($dedicated_table_field_schema as $name => $table) {
            $this->database->schema()->dropTable($dedicated_table_name_mapping[$name]);
          }
        }
        throw $e;
      }

      // Delete the field from the shared tables.
      $this->deleteSharedTableSchema($storage_definition);
    }
    unset($this->fieldStorageDefinitions[$storage_definition->getName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {
    $this->performFieldSchemaOperation('delete', $storage_definition);
  }

  /**
   * Returns a SELECT query suitable for inserting data into a dedicated table.
   *
   * @param string $table_name
   *   The entity table name to select from.
   * @param array $shared_table_field_columns
   *   An array of field column names for a shared table schema.
   * @param array $dedicated_table_field_columns
   *   An array of field column names for a dedicated table schema.
   * @param string $base_table
   *   (optional) The name of the base entity table. Defaults to NULL.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A database select query.
   */
  protected function getSelectQueryForFieldStorageDeletion($table_name, array $shared_table_field_columns, array $dedicated_table_field_columns, $base_table = NULL) {
    // Create a SELECT query that generates a result suitable for writing into
    // a dedicated field table.
    $select = $this->database->select($table_name, 'entity_table');

    // Add the bundle column.
    if ($bundle = $this->entityType->getKey('bundle')) {
      // The bundle field is not stored in the revision table, so we need to
      // join the data (or base) table and retrieve it from there.
      if ($base_table && $base_table !== $table_name) {
        $join_condition = "[entity_table].[{$this->entityType->getKey('id')}] = [%alias].[{$this->entityType->getKey('id')}]";

        // If the entity type is translatable, we also need to add the langcode
        // to the join, otherwise we'll get duplicate rows for each language.
        if ($this->entityType->isTranslatable()) {
          $langcode = $this->entityType->getKey('langcode');
          $join_condition .= " AND [entity_table].[{$langcode}] = [%alias].[{$langcode}]";
        }

        $select->join($base_table, 'base_table', $join_condition);
        $select->addField('base_table', $bundle, 'bundle');
      }
      else {
        $select->addField('entity_table', $bundle, 'bundle');
      }
    }
    else {
      $select->addExpression(':bundle', 'bundle', [':bundle' => $this->entityType->id()]);
    }

    // Add the deleted column.
    $select->addExpression(':deleted', 'deleted', [':deleted' => 1]);

    // Add the entity_id column.
    $select->addField('entity_table', $this->entityType->getKey('id'), 'entity_id');

    // Add the revision_id column.
    if ($this->entityType->isRevisionable()) {
      $select->addField('entity_table', $this->entityType->getKey('revision'), 'revision_id');
    }
    else {
      $select->addField('entity_table', $this->entityType->getKey('id'), 'revision_id');
    }

    // Add the langcode column.
    if ($langcode = $this->entityType->getKey('langcode')) {
      $select->addField('entity_table', $langcode, 'langcode');
    }
    else {
      $select->addExpression(':langcode', 'langcode', [':langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED]);
    }

    // Add the delta column and set it to 0 because we are only dealing with
    // single cardinality fields.
    $select->addExpression(':delta', 'delta', [':delta' => 0]);

    // Add all the dynamic field columns.
    $or = $select->orConditionGroup();
    foreach ($shared_table_field_columns as $field_column_name => $schema_column_name) {
      $select->addField('entity_table', $schema_column_name, $dedicated_table_field_columns[$field_column_name]);
      $or->isNotNull('entity_table.' . $schema_column_name);
    }
    $select->condition($or);

    // Lock the table rows.
    $select->forUpdate(TRUE);

    return $select;
  }

  /**
   * Checks that we are dealing with the correct entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to be checked.
   *
   * @return bool
   *   TRUE if the entity type matches the current one.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function checkEntityType(EntityTypeInterface $entity_type) {
    if ($entity_type->id() != $this->entityType->id()) {
      throw new EntityStorageException("Unsupported entity type {$entity_type->id()}");
    }
    return TRUE;
  }

  /**
   * Gets the entity schema for the specified entity type.
   *
   * Entity types may override this method in order to optimize the generated
   * schema of the entity tables. However, only cross-field optimizations should
   * be added here; e.g., an index spanning multiple fields. Optimizations that
   * apply to a single field have to be added via
   * SqlContentEntityStorageSchema::getSharedTableFieldSchema() instead.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type definition.
   * @param bool $reset
   *   (optional) If set to TRUE static cache will be ignored and a new schema
   *   array generation will be performed. Defaults to FALSE.
   *
   * @return array
   *   A Schema API array describing the entity schema, excluding dedicated
   *   field tables.
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $this->checkEntityType($entity_type);
    $entity_type_id = $entity_type->id();

    if (!isset($this->schema[$entity_type_id]) || $reset) {
      // Prepare basic information about the entity type.
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $this->getTableMapping($entity_type, $this->fieldStorageDefinitions);
      $tables = $this->getEntitySchemaTables($table_mapping);

      // Initialize the table schema.
      $schema[$tables['base_table']] = $this->initializeBaseTable($entity_type);
      if (isset($tables['revision_table'])) {
        $schema[$tables['revision_table']] = $this->initializeRevisionTable($entity_type);
      }
      if (isset($tables['data_table'])) {
        $schema[$tables['data_table']] = $this->initializeDataTable($entity_type);
      }
      if (isset($tables['revision_data_table'])) {
        $schema[$tables['revision_data_table']] = $this->initializeRevisionDataTable($entity_type);
      }

      // We need to act only on shared entity schema tables.
      $table_names = array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames());
      foreach ($table_names as $table_name) {
        if (!isset($schema[$table_name])) {
          $schema[$table_name] = [];
        }
        foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
          if (!isset($this->fieldStorageDefinitions[$field_name])) {
            throw new FieldException("Field storage definition for '$field_name' could not be found.");
          }
          // Add the schema for base field definitions.
          elseif ($table_mapping->allowsSharedTableStorage($this->fieldStorageDefinitions[$field_name])) {
            $column_names = $table_mapping->getColumnNames($field_name);
            $storage_definition = $this->fieldStorageDefinitions[$field_name];
            $schema[$table_name] = array_merge_recursive($schema[$table_name], $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names));
          }
        }
      }

      // Process tables after having gathered field information.
      if (isset($tables['data_table'])) {
        $this->processDataTable($entity_type, $schema[$tables['data_table']]);
      }
      if (isset($tables['revision_data_table'])) {
        $this->processRevisionDataTable($entity_type, $schema[$tables['revision_data_table']]);
      }

      // Add an index for the 'published' entity key.
      if (is_subclass_of($entity_type->getClass(), EntityPublishedInterface::class)) {
        $published_key = $entity_type->getKey('published');
        if ($published_key
            && isset($this->fieldStorageDefinitions[$published_key])
            && !$this->fieldStorageDefinitions[$published_key]->hasCustomStorage()) {
          $published_field_table = $table_mapping->getFieldTableName($published_key);
          $id_key = $entity_type->getKey('id');
          if ($bundle_key = $entity_type->getKey('bundle')) {
            $key = "{$published_key}_{$bundle_key}";
            $columns = [$published_key, $bundle_key, $id_key];
          }
          else {
            $key = $published_key;
            $columns = [$published_key, $id_key];
          }
          $schema[$published_field_table]['indexes'][$this->getEntityIndexName($entity_type, $key)] = $columns;
        }
      }

      $this->schema[$entity_type_id] = $schema;
    }

    return $this->schema[$entity_type_id];
  }

  /**
   * Gets a list of entity type tables.
   *
   * @param \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping
   *   A table mapping object.
   *
   * @return array
   *   A list of entity type tables, keyed by table key.
   */
  protected function getEntitySchemaTables(TableMappingInterface $table_mapping) {
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    return array_filter([
      'base_table' => $table_mapping->getBaseTable(),
      'revision_table' => $table_mapping->getRevisionTable(),
      'data_table' => $table_mapping->getDataTable(),
      'revision_data_table' => $table_mapping->getRevisionDataTable(),
    ]);
  }

  /**
   * Gets entity schema definitions for index and key definitions.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $schema
   *   The entity schema array.
   *
   * @return array
   *   A stripped down version of the $schema Schema API array containing, for
   *   each table, only the key and index definitions not derived from field
   *   storage definitions.
   */
  protected function getEntitySchemaData(ContentEntityTypeInterface $entity_type, array $schema) {
    $entity_type_id = $entity_type->id();

    // Collect all possible field schema identifiers for shared table fields.
    // These will be used to detect entity schema data in the subsequent loop.
    $field_schema_identifiers = [];
    $table_mapping = $this->getTableMapping($entity_type);
    foreach ($this->fieldStorageDefinitions as $field_name => $storage_definition) {
      if ($table_mapping->allowsSharedTableStorage($storage_definition)) {
        // Make sure both base identifier names and suffixed names are listed.
        $name = $this->getFieldSchemaIdentifierName($entity_type_id, $field_name);
        $field_schema_identifiers[$name] = $name;
        foreach ($storage_definition->getColumns() as $key => $columns) {
          $name = $this->getFieldSchemaIdentifierName($entity_type_id, $field_name, $key);
          $field_schema_identifiers[$name] = $name;
        }
      }
    }

    // Extract entity schema data from the Schema API definition.
    $schema_data = [];
    $keys = ['indexes', 'unique keys'];
    $unused_keys = array_flip(['description', 'fields', 'foreign keys']);
    foreach ($schema as $table_name => $table_schema) {
      $table_schema = array_diff_key($table_schema, $unused_keys);
      foreach ($keys as $key) {
        // Exclude data generated from field storage definitions, we will check
        // that separately.
        if ($field_schema_identifiers && !empty($table_schema[$key])) {
          $table_schema[$key] = array_diff_key($table_schema[$key], $field_schema_identifiers);
        }
      }
      $schema_data[$table_name] = array_filter($table_schema);
    }

    return $schema_data;
  }

  /**
   * Gets an index schema array for a given field.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array $field_schema
   *   The schema of the field.
   * @param string[] $column_mapping
   *   A mapping of field column names to database column names.
   *
   * @return array
   *   The schema definition for the indexes.
   */
  protected function getFieldIndexes($field_name, array $field_schema, array $column_mapping) {
    return $this->getFieldSchemaData($field_name, $field_schema, $column_mapping, 'indexes');
  }

  /**
   * Gets a unique key schema array for a given field.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array $field_schema
   *   The schema of the field.
   * @param string[] $column_mapping
   *   A mapping of field column names to database column names.
   *
   * @return array
   *   The schema definition for the unique keys.
   */
  protected function getFieldUniqueKeys($field_name, array $field_schema, array $column_mapping) {
    return $this->getFieldSchemaData($field_name, $field_schema, $column_mapping, 'unique keys');
  }

  /**
   * Gets field schema data for the given key.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array $field_schema
   *   The schema of the field.
   * @param string[] $column_mapping
   *   A mapping of field column names to database column names.
   * @param string $schema_key
   *   The type of schema data. Either 'indexes' or 'unique keys'.
   *
   * @return array
   *   The schema definition for the specified key.
   */
  protected function getFieldSchemaData($field_name, array $field_schema, array $column_mapping, $schema_key) {
    $data = [];

    $entity_type_id = $this->entityType->id();
    foreach ($field_schema[$schema_key] as $key => $columns) {
      // To avoid clashes with entity-level indexes or unique keys we use
      // "{$entity_type_id}_field__" as a prefix instead of just
      // "{$entity_type_id}__". We additionally namespace the specifier by the
      // field name to avoid clashes when multiple fields of the same type are
      // added to an entity type.
      $real_key = $this->getFieldSchemaIdentifierName($entity_type_id, $field_name, $key);
      foreach ($columns as $column) {
        // Allow for indexes and unique keys to specified as an array of column
        // name and length.
        if (is_array($column)) {
          [$column_name, $length] = $column;
          $data[$real_key][] = [$column_mapping[$column_name], $length];
        }
        else {
          $data[$real_key][] = $column_mapping[$column];
        }
      }
    }

    return $data;
  }

  /**
   * Generates a safe schema identifier (name of an index, column name etc.).
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param string $field_name
   *   The name of the field.
   * @param string|null $key
   *   (optional) A further key to append to the name.
   *
   * @return string
   *   The field identifier name.
   */
  protected function getFieldSchemaIdentifierName($entity_type_id, $field_name, $key = NULL) {
    $real_key = isset($key) ? "{$entity_type_id}_field__{$field_name}__{$key}" : "{$entity_type_id}_field__{$field_name}";
    // Limit the string to 48 characters, keeping a 16 characters margin for db
    // prefixes.
    if (strlen($real_key) > 48) {
      // Use a shorter separator, a truncated entity_type, and a hash of the
      // field name.
      // Truncate to the same length for the current and revision tables.
      $entity_type = substr($entity_type_id, 0, 36);
      $field_hash = substr(hash('sha256', $real_key), 0, 10);
      $real_key = $entity_type . '__' . $field_hash;
    }
    return $real_key;
  }

  /**
   * Gets field foreign keys.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array $field_schema
   *   The schema of the field.
   * @param string[] $column_mapping
   *   A mapping of field column names to database column names.
   *
   * @return array
   *   The schema definition for the foreign keys.
   */
  protected function getFieldForeignKeys($field_name, array $field_schema, array $column_mapping) {
    $foreign_keys = [];

    foreach ($field_schema['foreign keys'] as $specifier => $specification) {
      // To avoid clashes with entity-level foreign keys we use
      // "{$entity_type_id}_field__" as a prefix instead of just
      // "{$entity_type_id}__". We additionally namespace the specifier by the
      // field name to avoid clashes when multiple fields of the same type are
      // added to an entity type.
      $entity_type_id = $this->entityType->id();
      $real_specifier = "{$entity_type_id}_field__{$field_name}__{$specifier}";
      $foreign_keys[$real_specifier]['table'] = $specification['table'];
      foreach ($specification['columns'] as $column => $referenced) {
        $foreign_keys[$real_specifier]['columns'][$column_mapping[$column]] = $referenced;
      }
    }

    return $foreign_keys;
  }

  /**
   * Loads stored schema data for the given entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return array
   *   The entity schema data array.
   */
  protected function loadEntitySchemaData(EntityTypeInterface $entity_type) {
    return $this->installedStorageSchema()->get($entity_type->id() . '.entity_schema_data', []);
  }

  /**
   * Stores schema data for the given entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $schema
   *   The entity schema data array.
   */
  protected function saveEntitySchemaData(EntityTypeInterface $entity_type, $schema) {
    $data = $this->getEntitySchemaData($entity_type, $schema);
    $this->installedStorageSchema()->set($entity_type->id() . '.entity_schema_data', $data);
  }

  /**
   * Deletes schema data for the given entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  protected function deleteEntitySchemaData(EntityTypeInterface $entity_type) {
    $this->installedStorageSchema()->delete($entity_type->id() . '.entity_schema_data');
  }

  /**
   * Loads stored schema data for the given field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   *
   * @return array
   *   The field schema data array.
   */
  protected function loadFieldSchemaData(FieldStorageDefinitionInterface $storage_definition) {
    return $this->installedStorageSchema()->get($storage_definition->getTargetEntityTypeId() . '.field_schema_data.' . $storage_definition->getName(), []);
  }

  /**
   * Stores schema data for the given field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param array $schema
   *   The field schema data array.
   */
  protected function saveFieldSchemaData(FieldStorageDefinitionInterface $storage_definition, $schema) {
    $this->processFieldStorageSchema($schema);
    $this->installedStorageSchema()->set($storage_definition->getTargetEntityTypeId() . '.field_schema_data.' . $storage_definition->getName(), $schema);
  }

  /**
   * Deletes schema data for the given field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  protected function deleteFieldSchemaData(FieldStorageDefinitionInterface $storage_definition) {
    $this->installedStorageSchema()->delete($storage_definition->getTargetEntityTypeId() . '.field_schema_data.' . $storage_definition->getName());
  }

  /**
   * Initializes common information for a base table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function initializeBaseTable(ContentEntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();

    $schema = [
      'description' => "The base table for $entity_type_id entities.",
      'primary key' => [$entity_type->getKey('id')],
      'indexes' => [],
      'foreign keys' => [],
    ];

    if ($entity_type->hasKey('revision')) {
      $revision_key = $entity_type->getKey('revision');
      $key_name = $this->getEntityIndexName($entity_type, $revision_key);
      $schema['unique keys'][$key_name] = [$revision_key];
      $schema['foreign keys'][$entity_type_id . '__revision'] = [
        'table' => $this->storage->getRevisionTable(),
        'columns' => [$revision_key => $revision_key],
      ];
    }

    $this->addTableDefaults($schema);

    return $schema;
  }

  /**
   * Initializes common information for a revision table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   A partial schema array for the revision table.
   */
  protected function initializeRevisionTable(ContentEntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');

    $schema = [
      'description' => "The revision table for $entity_type_id entities.",
      'primary key' => [$revision_key],
      'indexes' => [],
      'foreign keys' => [
        $entity_type_id . '__revisioned' => [
          'table' => $this->storage->getBaseTable(),
          'columns' => [$id_key => $id_key],
        ],
      ],
    ];

    $schema['indexes'][$this->getEntityIndexName($entity_type, $id_key)] = [$id_key];

    $this->addTableDefaults($schema);

    return $schema;
  }

  /**
   * Initializes common information for a data table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   A partial schema array for the data table.
   */
  protected function initializeDataTable(ContentEntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $id_key = $entity_type->getKey('id');

    $schema = [
      'description' => "The data table for $entity_type_id entities.",
      'primary key' => [$id_key, $entity_type->getKey('langcode')],
      'indexes' => [
        $entity_type_id . '__id__default_langcode__langcode' => [$id_key, $entity_type->getKey('default_langcode'), $entity_type->getKey('langcode')],
      ],
      'foreign keys' => [
        $entity_type_id => [
          'table' => $this->storage->getBaseTable(),
          'columns' => [$id_key => $id_key],
        ],
      ],
    ];

    if ($entity_type->hasKey('revision')) {
      $key = $entity_type->getKey('revision');
      $schema['indexes'][$this->getEntityIndexName($entity_type, $key)] = [$key];
    }

    $this->addTableDefaults($schema);

    return $schema;
  }

  /**
   * Initializes common information for a revision data table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   A partial schema array for the revision data table.
   */
  protected function initializeRevisionDataTable(ContentEntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');

    $schema = [
      'description' => "The revision data table for $entity_type_id entities.",
      'primary key' => [$revision_key, $entity_type->getKey('langcode')],
      'indexes' => [
        $entity_type_id . '__id__default_langcode__langcode' => [$id_key, $entity_type->getKey('default_langcode'), $entity_type->getKey('langcode')],
      ],
      'foreign keys' => [
        $entity_type_id => [
          'table' => $this->storage->getBaseTable(),
          'columns' => [$id_key => $id_key],
        ],
        $entity_type_id . '__revision' => [
          'table' => $this->storage->getRevisionTable(),
          'columns' => [$revision_key => $revision_key],
        ],
      ],
    ];

    $this->addTableDefaults($schema);

    return $schema;
  }

  /**
   * Adds defaults to a table schema definition.
   *
   * @param $schema
   *   The schema definition array for a single table, passed by reference.
   */
  protected function addTableDefaults(&$schema) {
    $schema += [
      'fields' => [],
      'unique keys' => [],
      'indexes' => [],
      'foreign keys' => [],
    ];
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param array $schema
   *   The table schema, passed by reference.
   */
  protected function processDataTable(ContentEntityTypeInterface $entity_type, array &$schema) {
    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['fields'][$entity_type->getKey('default_langcode')]['not null'] = TRUE;
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param array &$schema
   *   The table schema, passed by reference.
   */
  protected function processRevisionDataTable(ContentEntityTypeInterface $entity_type, array &$schema) {
    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['fields'][$entity_type->getKey('default_langcode')]['not null'] = TRUE;
  }

  /**
   * Processes the specified entity key.
   *
   * @param array $schema
   *   The table schema, passed by reference.
   * @param string $key
   *   The entity key name.
   */
  protected function processIdentifierSchema(&$schema, $key) {
    if ($schema['fields'][$key]['type'] == 'int') {
      $schema['fields'][$key]['type'] = 'serial';
    }
    $schema['fields'][$key]['not null'] = TRUE;
    unset($schema['fields'][$key]['default']);
  }

  /**
   * Processes the schema for a field storage definition.
   *
   * @param array &$field_storage_schema
   *   An array that contains the schema data for a field storage definition.
   */
  protected function processFieldStorageSchema(array &$field_storage_schema) {
    // Clean up some schema properties that should not be taken into account
    // after a field storage has been created.
    foreach ($field_storage_schema as $table_name => $table_schema) {
      foreach ($table_schema['fields'] as $key => $schema) {
        unset($field_storage_schema[$table_name]['fields'][$key]['initial']);
        unset($field_storage_schema[$table_name]['fields'][$key]['initial_from_field']);
      }
    }
  }

  /**
   * Performs the specified operation on a field.
   *
   * This figures out whether the field is stored in a dedicated or shared table
   * and forwards the call to the proper handler.
   *
   * @param string $operation
   *   The name of the operation to be performed.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   (optional) The original field storage definition. This is relevant (and
   *   required) only for updates. Defaults to NULL.
   */
  protected function performFieldSchemaOperation($operation, FieldStorageDefinitionInterface $storage_definition, ?FieldStorageDefinitionInterface $original = NULL) {
    $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $this->{$operation . 'DedicatedTableSchema'}($storage_definition, $original);
    }
    elseif ($table_mapping->allowsSharedTableStorage($storage_definition)) {
      $this->{$operation . 'SharedTableSchema'}($storage_definition, $original);
    }
  }

  /**
   * Creates the schema for a field stored in a dedicated table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field being created.
   * @param bool $only_save
   *   (optional) Whether to skip modification of database tables and only save
   *   the schema data for future comparison. For internal use only. This is
   *   used by postUpdateEntityTypeSchema() after it has already fully created
   *   the dedicated tables.
   */
  protected function createDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition, $only_save = FALSE) {
    $schema = $this->getDedicatedTableSchema($storage_definition);

    if (!$only_save) {
      foreach ($schema as $name => $table) {
        // Check if the table exists because it might already have been
        // created as part of the earlier entity type update event.
        if (!$this->database->schema()->tableExists($name)) {
          $this->database->schema()->createTable($name, $table);
        }
      }
    }
    $this->saveFieldSchemaData($storage_definition, $schema);
  }

  /**
   * Creates the schema for a field stored in a shared table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field being created.
   * @param bool $only_save
   *   (optional) Whether to skip modification of database tables and only save
   *   the schema data for future comparison. For internal use only. This is
   *   used by onEntityTypeCreate() after it has already fully created the
   *   shared tables.
   */
  protected function createSharedTableSchema(FieldStorageDefinitionInterface $storage_definition, $only_save = FALSE) {
    $created_field_name = $storage_definition->getName();
    $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
    $column_names = $table_mapping->getColumnNames($created_field_name);
    $schema_handler = $this->database->schema();
    $shared_table_names = array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames());

    // Iterate over the mapped table to find the ones that will host the created
    // field schema.
    $schema = [];
    foreach ($shared_table_names as $table_name) {
      foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
        if ($field_name == $created_field_name) {
          // Create field columns.
          $schema[$table_name] = $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names);
          if (!$only_save) {
            // The entity schema needs to be checked because the field schema is
            // potentially incomplete.
            // @todo Fix this in https://www.drupal.org/node/2929120.
            $entity_schema = $this->getEntitySchema($this->entityType);
            foreach ($schema[$table_name]['fields'] as $name => $specifier) {
              // Check if the field is part of the primary keys and pass along
              // this information when adding the field.
              // @see \Drupal\Core\Database\Schema::addField()
              $new_keys = [];
              if (isset($entity_schema[$table_name]['primary key']) && array_intersect($column_names, $entity_schema[$table_name]['primary key'])) {
                $new_keys = ['primary key' => $entity_schema[$table_name]['primary key']];
              }

              // Check if the field exists because it might already have been
              // created as part of the earlier entity type update event.
              if (!$schema_handler->fieldExists($table_name, $name)) {
                $schema_handler->addField($table_name, $name, $specifier, $new_keys);
              }
            }
            if (!empty($schema[$table_name]['indexes'])) {
              foreach ($schema[$table_name]['indexes'] as $name => $specifier) {
                // Check if the index exists because it might already have been
                // created as part of the earlier entity type update event.
                $this->addIndex($table_name, $name, $specifier, $schema[$table_name]);
              }
            }
            if (!empty($schema[$table_name]['unique keys'])) {
              foreach ($schema[$table_name]['unique keys'] as $name => $specifier) {
                $schema_handler->addUniqueKey($table_name, $name, $specifier);
              }
            }
          }
          // After creating the field schema skip to the next table.
          break;
        }
      }
    }

    $this->saveFieldSchemaData($storage_definition, $schema);

    if (!$only_save) {
      // Make sure any entity index involving this field is re-created if
      // needed.
      $entity_schema = $this->getEntitySchema($this->entityType);
      $this->createEntitySchemaIndexes($entity_schema, $storage_definition);

      // Store the updated entity schema.
      $this->saveEntitySchemaData($this->entityType, $entity_schema);
    }
  }

  /**
   * Deletes the schema for a field stored in a dedicated table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field being deleted.
   */
  protected function deleteDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition) {
    $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
    $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $storage_definition->isDeleted());
    if ($this->database->schema()->tableExists($table_name)) {
      $this->database->schema()->dropTable($table_name);
    }
    if ($this->entityType->isRevisionable()) {
      $revision_table_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $storage_definition->isDeleted());
      if ($this->database->schema()->tableExists($revision_table_name)) {
        $this->database->schema()->dropTable($revision_table_name);
      }
    }
    $this->deleteFieldSchemaData($storage_definition);
  }

  /**
   * Deletes the schema for a field stored in a shared table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field being deleted.
   */
  protected function deleteSharedTableSchema(FieldStorageDefinitionInterface $storage_definition) {
    // Make sure any entity index involving this field is dropped.
    $this->deleteEntitySchemaIndexes($this->loadEntitySchemaData($this->entityType), $storage_definition);

    $deleted_field_name = $storage_definition->getName();
    $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
    $column_names = $table_mapping->getColumnNames($deleted_field_name);
    $schema_handler = $this->database->schema();
    $shared_table_names = array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames());

    // Iterate over the mapped table to find the ones that host the deleted
    // field schema.
    foreach ($shared_table_names as $table_name) {
      foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
        if ($field_name == $deleted_field_name) {
          $schema = $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names);

          // Drop indexes and unique keys first.
          if (!empty($schema['indexes'])) {
            foreach ($schema['indexes'] as $name => $specifier) {
              $schema_handler->dropIndex($table_name, $name);
            }
          }
          if (!empty($schema['unique keys'])) {
            foreach ($schema['unique keys'] as $name => $specifier) {
              $schema_handler->dropUniqueKey($table_name, $name);
            }
          }
          // Drop columns.
          foreach ($column_names as $column_name) {
            $schema_handler->dropField($table_name, $column_name);
          }
          // After deleting the field schema skip to the next table.
          break;
        }
      }
    }

    $this->deleteFieldSchemaData($storage_definition);
  }

  /**
   * Updates the schema for a field stored in a shared table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field being updated.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original storage definition; i.e., the definition before the update.
   *
   * @throws \Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException
   *   Thrown when the update to the field is forbidden.
   * @throws \Exception
   *   Rethrown exception if the table recreation fails.
   */
  protected function updateDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    if (!$this->storage->countFieldData($original, TRUE)) {
      // There is no data. Re-create the tables completely.
      try {
        if ($this->database->supportsTransactionalDDL()) {
          // If the database supports transactional DDL, we can go ahead and rely
          // on it. If not, we will have to rollback manually if something fails.
          $transaction = $this->database->startTransaction();
        }
        // Since there is no data we may be switching from a shared table schema
        // to a dedicated table schema, hence we should use the proper API.
        $this->performFieldSchemaOperation('delete', $original);
        $this->performFieldSchemaOperation('create', $storage_definition);
      }
      catch (\Exception $e) {
        if ($this->database->supportsTransactionalDDL()) {
          if (isset($transaction)) {
            $transaction->rollBack();
          }
        }
        else {
          // Recreate tables.
          $this->performFieldSchemaOperation('create', $original);
        }
        throw $e;
      }
    }
    else {
      if (empty($storage_definition->getSetting('column_changes_handled')) && $this->hasColumnChanges($storage_definition, $original)) {
        throw new FieldStorageDefinitionUpdateForbiddenException('The SQL storage cannot change the schema for an existing field (' . $storage_definition->getName() . ' in ' . $storage_definition->getTargetEntityTypeId() . ' entity) with data.');
      }
      // There is data, so there are no column changes. Drop all the prior
      // indexes and create all the new ones, except for all the priors that
      // exist unchanged.
      $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
      $table = $table_mapping->getDedicatedDataTableName($original);
      $revision_table = $table_mapping->getDedicatedRevisionTableName($original);

      // Get the field schemas.
      $schema = $storage_definition->getSchema();
      $original_schema = $original->getSchema();

      // Gets the SQL schema for a dedicated tables.
      $actual_schema = $this->getDedicatedTableSchema($storage_definition);

      foreach ($original_schema['indexes'] as $name => $columns) {
        if (!isset($schema['indexes'][$name]) || $columns != $schema['indexes'][$name]) {
          $real_name = $this->getFieldIndexName($storage_definition, $name);
          $this->database->schema()->dropIndex($table, $real_name);
          $this->database->schema()->dropIndex($revision_table, $real_name);
        }
      }
      $table = $table_mapping->getDedicatedDataTableName($storage_definition);
      $revision_table = $table_mapping->getDedicatedRevisionTableName($storage_definition);
      foreach ($schema['indexes'] as $name => $columns) {
        if (!isset($original_schema['indexes'][$name]) || $columns != $original_schema['indexes'][$name]) {
          $real_name = $this->getFieldIndexName($storage_definition, $name);
          $real_columns = [];
          foreach ($columns as $column_name) {
            // Indexes can be specified as either a column name or an array with
            // column name and length. Allow for either case.
            if (is_array($column_name)) {
              $real_columns[] = [
                $table_mapping->getFieldColumnName($storage_definition, $column_name[0]),
                $column_name[1],
              ];
            }
            else {
              $real_columns[] = $table_mapping->getFieldColumnName($storage_definition, $column_name);
            }
          }
          // Check if the index exists because it might already have been
          // created as part of the earlier entity type update event.
          $this->addIndex($table, $real_name, $real_columns, $actual_schema[$table]);
          $this->addIndex($revision_table, $real_name, $real_columns, $actual_schema[$revision_table]);
        }
      }
      $this->saveFieldSchemaData($storage_definition, $this->getDedicatedTableSchema($storage_definition));
    }
  }

  /**
   * Updates the schema for a field stored in a shared table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field being updated.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original storage definition; i.e., the definition before the update.
   *
   * @throws \Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException
   *   Thrown when the update to the field is forbidden.
   * @throws \Exception
   *   Rethrown exception if the table recreation fails.
   */
  protected function updateSharedTableSchema(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    if (!$this->storage->countFieldData($original, TRUE)) {
      try {
        if ($this->database->supportsTransactionalDDL()) {
          // If the database supports transactional DDL, we can go ahead and rely
          // on it. If not, we will have to rollback manually if something fails.
          $transaction = $this->database->startTransaction();
        }
        // Since there is no data we may be switching from a dedicated table
        // to a schema table schema, hence we should use the proper API.
        $this->performFieldSchemaOperation('delete', $original);
        $this->performFieldSchemaOperation('create', $storage_definition);
      }
      catch (\Exception $e) {
        if ($this->database->supportsTransactionalDDL()) {
          if (isset($transaction)) {
            $transaction->rollBack();
          }
        }
        else {
          // Recreate original schema.
          $this->createSharedTableSchema($original);
        }
        throw $e;
      }
    }
    else {
      if (empty($storage_definition->getSetting('column_changes_handled')) && $this->hasColumnChanges($storage_definition, $original)) {
        throw new FieldStorageDefinitionUpdateForbiddenException('The SQL storage cannot change the schema for an existing field (' . $storage_definition->getName() . ' in ' . $storage_definition->getTargetEntityTypeId() . ' entity) with data.');
      }

      $updated_field_name = $storage_definition->getName();
      $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
      $column_names = $table_mapping->getColumnNames($updated_field_name);
      $schema_handler = $this->database->schema();

      // Iterate over the mapped table to find the ones that host the deleted
      // field schema.
      $original_schema = $this->loadFieldSchemaData($original);
      $schema = [];
      foreach ($table_mapping->getTableNames() as $table_name) {
        foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
          if ($field_name == $updated_field_name) {
            $schema[$table_name] = $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names);

            // Handle NOT NULL constraints.
            foreach ($schema[$table_name]['fields'] as $column_name => $specifier) {
              $not_null = !empty($specifier['not null']);
              $original_not_null = !empty($original_schema[$table_name]['fields'][$column_name]['not null']);
              if ($not_null !== $original_not_null) {
                if ($not_null && $this->hasNullFieldPropertyData($table_name, $column_name)) {
                  throw new EntityStorageException("The $column_name column cannot have NOT NULL constraints as it holds NULL values.");
                }
                $column_schema = $original_schema[$table_name]['fields'][$column_name];
                $column_schema['not null'] = $not_null;
                $schema_handler->changeField($table_name, $column_name, $column_name, $column_schema);
              }
            }

            // Drop original indexes and unique keys.
            if (!empty($original_schema[$table_name]['indexes'])) {
              foreach ($original_schema[$table_name]['indexes'] as $name => $specifier) {
                $schema_handler->dropIndex($table_name, $name);
              }
            }
            if (!empty($original_schema[$table_name]['unique keys'])) {
              foreach ($original_schema[$table_name]['unique keys'] as $name => $specifier) {
                $schema_handler->dropUniqueKey($table_name, $name);
              }
            }
            // Create new indexes and unique keys.
            if (!empty($schema[$table_name]['indexes'])) {
              foreach ($schema[$table_name]['indexes'] as $name => $specifier) {
                // Check if the index exists because it might already have been
                // created as part of the earlier entity type update event.
                $this->addIndex($table_name, $name, $specifier, $schema[$table_name]);

              }
            }
            if (!empty($schema[$table_name]['unique keys'])) {
              foreach ($schema[$table_name]['unique keys'] as $name => $specifier) {
                $schema_handler->addUniqueKey($table_name, $name, $specifier);
              }
            }
            // After deleting the field schema skip to the next table.
            break;
          }
        }
      }
      $this->saveFieldSchemaData($storage_definition, $schema);
    }
  }

  /**
   * Creates the specified entity schema indexes and keys.
   *
   * @param array $entity_schema
   *   The entity schema definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $storage_definition
   *   (optional) If a field storage definition is specified, only indexes and
   *   keys involving its columns will be processed. Otherwise all defined
   *   entity indexes and keys will be processed.
   */
  protected function createEntitySchemaIndexes(array $entity_schema, ?FieldStorageDefinitionInterface $storage_definition = NULL) {
    $schema_handler = $this->database->schema();

    if ($storage_definition) {
      $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
      $column_names = $table_mapping->getColumnNames($storage_definition->getName());
    }

    $index_keys = [
      'indexes' => 'addIndex',
      'unique keys' => 'addUniqueKey',
    ];

    foreach ($this->getEntitySchemaData($this->entityType, $entity_schema) as $table_name => $schema) {
      // Add fields schema because database driver may depend on this data to
      // perform index normalization.
      $schema['fields'] = $entity_schema[$table_name]['fields'];

      foreach ($index_keys as $key => $add_method) {
        if (!empty($schema[$key])) {
          foreach ($schema[$key] as $name => $specifier) {
            // If a set of field columns were specified we process only indexes
            // involving them. Only indexes for which all columns exist are
            // actually created.
            $create = FALSE;
            $specifier_columns = array_map(function ($item) {
              return is_string($item) ? $item : reset($item);
            }, $specifier);
            if (!isset($column_names) || array_intersect($specifier_columns, $column_names)) {
              $create = TRUE;
              foreach ($specifier_columns as $specifier_column_name) {
                // This may happen when adding more than one field in the same
                // update run. Eventually when all field columns have been
                // created the index will be created too.
                if (!$schema_handler->fieldExists($table_name, $specifier_column_name)) {
                  $create = FALSE;
                  break;
                }
              }
            }
            if ($create) {
              $this->{$add_method}($table_name, $name, $specifier, $schema);
            }
          }
        }
      }
    }
  }

  /**
   * Deletes the specified entity schema indexes and keys.
   *
   * @param array $entity_schema_data
   *   The entity schema data definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $storage_definition
   *   (optional) If a field storage definition is specified, only indexes and
   *   keys involving its columns will be processed. Otherwise all defined
   *   entity indexes and keys will be processed.
   */
  protected function deleteEntitySchemaIndexes(array $entity_schema_data, ?FieldStorageDefinitionInterface $storage_definition = NULL) {
    $schema_handler = $this->database->schema();

    if ($storage_definition) {
      $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
      $column_names = $table_mapping->getColumnNames($storage_definition->getName());
    }

    $index_keys = [
      'indexes' => 'dropIndex',
      'unique keys' => 'dropUniqueKey',
    ];

    foreach ($entity_schema_data as $table_name => $schema) {
      foreach ($index_keys as $key => $drop_method) {
        if (!empty($schema[$key])) {
          foreach ($schema[$key] as $name => $specifier) {
            $specifier_columns = array_map(function ($item) {
              return is_string($item) ? $item : reset($item);
            }, $specifier);
            if (!isset($column_names) || array_intersect($specifier_columns, $column_names)) {
              $schema_handler->{$drop_method}($table_name, $name);
            }
          }
        }
      }
    }
  }

  /**
   * Checks whether a field property has NULL values.
   *
   * @param string $table_name
   *   The name of the table to inspect.
   * @param string $column_name
   *   The name of the column holding the field property data.
   *
   * @return bool
   *   TRUE if NULL data is found, FALSE otherwise.
   */
  protected function hasNullFieldPropertyData($table_name, $column_name) {
    $query = $this->database->select($table_name, 't')
      ->fields('t', [$column_name])
      ->range(0, 1);
    $query->isNull('t.' . $column_name);
    $result = $query->execute()->fetchAssoc();
    return (bool) $result;
  }

  /**
   * Gets the schema for a single field definition.
   *
   * Entity types may override this method in order to optimize the generated
   * schema for given field. While all optimizations that apply to a single
   * field have to be added here, all cross-field optimizations should be via
   * SqlContentEntityStorageSchema::getEntitySchema() instead; e.g.,
   * an index spanning multiple fields.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field whose schema has to be returned.
   * @param string $table_name
   *   The name of the table columns will be added to.
   * @param string[] $column_mapping
   *   A mapping of field column names to database column names.
   *
   * @return array
   *   The schema definition for the table with the following keys:
   *   - fields: The schema definition for the each field columns.
   *   - indexes: The schema definition for the indexes.
   *   - unique keys: The schema definition for the unique keys.
   *   - foreign keys: The schema definition for the foreign keys.
   *
   * @throws \Drupal\Core\Field\FieldException
   *   Exception thrown if the schema contains reserved column names or if the
   *   initial values definition is invalid.
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = [];
    $table_mapping = $this->getTableMapping($this->entityType, [$storage_definition]);
    $field_schema = $storage_definition->getSchema();

    // Check that the schema does not include forbidden column names.
    if (array_intersect(array_keys($field_schema['columns']), $table_mapping->getReservedColumns())) {
      throw new FieldException("Illegal field column names on {$storage_definition->getName()}");
    }

    $field_name = $storage_definition->getName();
    $base_table = $this->storage->getBaseTable();
    $revision_table = $this->storage->getRevisionTable();

    // Define the initial values, if any.
    $initial_value = $initial_value_from_field = [];
    $storage_definition_is_new = empty($this->loadFieldSchemaData($storage_definition));
    if ($storage_definition_is_new && $storage_definition instanceof BaseFieldDefinition && $table_mapping->allowsSharedTableStorage($storage_definition)) {
      if (($initial_storage_value = $storage_definition->getInitialValue()) && !empty($initial_storage_value)) {
        // We only support initial values for fields that are stored in shared
        // tables (i.e. single-value fields).
        // @todo Implement initial value support for multi-value fields in
        //   https://www.drupal.org/node/2883851.
        $initial_value = reset($initial_storage_value);
      }

      if ($initial_value_field_name = $storage_definition->getInitialValueFromField()) {
        // Check that the field used for populating initial values is valid.
        if (!isset($this->fieldStorageDefinitions[$initial_value_field_name])) {
          throw new FieldException("Illegal initial value definition on {$storage_definition->getName()}: The field $initial_value_field_name does not exist.");
        }

        if ($storage_definition->getType() !== $this->fieldStorageDefinitions[$initial_value_field_name]->getType()) {
          throw new FieldException("Illegal initial value definition on {$storage_definition->getName()}: The field types do not match.");
        }

        if (!$table_mapping->allowsSharedTableStorage($this->fieldStorageDefinitions[$initial_value_field_name])) {
          throw new FieldException("Illegal initial value definition on {$storage_definition->getName()}: Both fields have to be stored in the shared entity tables.");
        }

        $initial_value_from_field = $table_mapping->getColumnNames($initial_value_field_name);
      }
    }

    // A shared table contains rows for entities where the field is empty
    // (since other fields stored in the same table might not be empty), thus
    // the only columns that can be 'not null' are those for required
    // properties of required fields. For now, we only hardcode 'not null' to a
    // few "entity keys", in order to keep their indexes optimized.
    // @todo Fix this in https://www.drupal.org/node/2841291.
    $not_null_keys = $this->entityType->getKeys();
    // Label and the 'revision_translation_affected' fields are not necessarily
    // required.
    unset($not_null_keys['label'], $not_null_keys['revision_translation_affected']);
    // Because entity ID and revision ID are both serial fields in the base and
    // revision table respectively, the revision ID is not known yet, when
    // inserting data into the base table. Instead the revision ID in the base
    // table is updated after the data has been inserted into the revision
    // table. For this reason the revision ID field cannot be marked as NOT
    // NULL.
    if ($table_name == $base_table) {
      unset($not_null_keys['revision']);
    }

    foreach ($column_mapping as $field_column_name => $schema_field_name) {
      $column_schema = $field_schema['columns'][$field_column_name];

      $schema['fields'][$schema_field_name] = $column_schema;
      $schema['fields'][$schema_field_name]['not null'] = in_array($field_name, $not_null_keys);

      // Use the initial value of the field storage, if available.
      if ($initial_value && isset($initial_value[$field_column_name])) {
        $schema['fields'][$schema_field_name]['initial'] = SqlContentEntityStorageSchema::castValue($column_schema, $initial_value[$field_column_name]);
      }
      if (!empty($initial_value_from_field)) {
        $schema['fields'][$schema_field_name]['initial_from_field'] = $initial_value_from_field[$field_column_name];
      }
    }

    if (!empty($field_schema['indexes'])) {
      $schema['indexes'] = $this->getFieldIndexes($field_name, $field_schema, $column_mapping);
    }

    if (!empty($field_schema['unique keys'])) {
      $schema['unique keys'] = $this->getFieldUniqueKeys($field_name, $field_schema, $column_mapping);
    }

    if (!empty($field_schema['foreign keys'])) {
      $schema['foreign keys'] = $this->getFieldForeignKeys($field_name, $field_schema, $column_mapping);
    }

    // Process the 'id' and 'revision' entity keys for the base and revision
    // tables.
    if (($table_name === $base_table && $field_name === $this->entityType->getKey('id')) ||
      ($table_name === $revision_table && $field_name === $this->entityType->getKey('revision'))) {
      $this->processIdentifierSchema($schema, $field_name);
    }

    return $schema;
  }

  /**
   * Adds an index for the specified field to the given schema definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field for which an index should be added.
   * @param array $schema
   *   A reference to the schema array to be updated.
   * @param bool $not_null
   *   (optional) Whether to also add a 'not null' constraint to the column
   *   being indexed. Doing so improves index performance. Defaults to FALSE,
   *   in case the field needs to support NULL values.
   * @param int $size
   *   (optional) The index size. Defaults to no limit.
   */
  protected function addSharedTableFieldIndex(FieldStorageDefinitionInterface $storage_definition, &$schema, $not_null = FALSE, $size = NULL) {
    $name = $storage_definition->getName();
    $real_key = $this->getFieldSchemaIdentifierName($storage_definition->getTargetEntityTypeId(), $name);
    $schema['indexes'][$real_key] = [$size ? [$name, $size] : $name];
    if ($not_null) {
      $schema['fields'][$name]['not null'] = TRUE;
    }
  }

  /**
   * Adds a unique key for the specified field to the given schema definition.
   *
   * Also adds a 'not null' constraint, because many databases do not reliably
   * support unique keys on null columns.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field to which to add a unique key.
   * @param array $schema
   *   A reference to the schema array to be updated.
   */
  protected function addSharedTableFieldUniqueKey(FieldStorageDefinitionInterface $storage_definition, &$schema) {
    $name = $storage_definition->getName();
    $real_key = $this->getFieldSchemaIdentifierName($storage_definition->getTargetEntityTypeId(), $name);
    $schema['unique keys'][$real_key] = [$name];
    $schema['fields'][$name]['not null'] = TRUE;
  }

  /**
   * Adds a foreign key for the specified field to the given schema definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field to which to add a foreign key.
   * @param array $schema
   *   A reference to the schema array to be updated.
   * @param string $foreign_table
   *   The foreign table.
   * @param string $foreign_column
   *   The foreign column.
   */
  protected function addSharedTableFieldForeignKey(FieldStorageDefinitionInterface $storage_definition, &$schema, $foreign_table, $foreign_column) {
    $name = $storage_definition->getName();
    $real_key = $this->getFieldSchemaIdentifierName($storage_definition->getTargetEntityTypeId(), $name);
    $schema['foreign keys'][$real_key] = [
      'table' => $foreign_table,
      'columns' => [$name => $foreign_column],
    ];
  }

  /**
   * Gets the SQL schema for a dedicated table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   (optional) The entity type definition. Defaults to the one provided by
   *   the entity storage.
   *
   * @return array
   *   The schema definition for the table with the following keys:
   *   - fields: The schema definition for the each field columns.
   *   - indexes: The schema definition for the indexes.
   *   - unique keys: The schema definition for the unique keys.
   *   - foreign keys: The schema definition for the foreign keys.
   *
   * @throws \Drupal\Core\Field\FieldException
   *   Exception thrown if the schema contains reserved column names.
   *
   * @see hook_schema()
   */
  protected function getDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition, ?ContentEntityTypeInterface $entity_type = NULL) {
    $entity_type = $entity_type ?: $this->entityType;
    $description_current = "Data storage for {$storage_definition->getTargetEntityTypeId()} field {$storage_definition->getName()}.";
    $description_revision = "Revision archive storage for {$storage_definition->getTargetEntityTypeId()} field {$storage_definition->getName()}.";

    $id_definition = $this->fieldStorageDefinitions[$entity_type->getKey('id')];
    if ($id_definition->getType() == 'integer') {
      $id_schema = [
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The entity id this data is attached to',
      ];
    }
    else {
      $id_schema = [
        'type' => 'varchar_ascii',
        'length' => 128,
        'not null' => TRUE,
        'description' => 'The entity id this data is attached to',
      ];
    }

    // Define the revision ID schema.
    if (!$entity_type->isRevisionable()) {
      $revision_id_schema = $id_schema;
      $revision_id_schema['description'] = 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id';
    }
    elseif ($this->fieldStorageDefinitions[$entity_type->getKey('revision')]->getType() == 'integer') {
      $revision_id_schema = [
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The entity revision id this data is attached to',
      ];
    }
    else {
      $revision_id_schema = [
        'type' => 'varchar',
        'length' => 128,
        'not null' => TRUE,
        'description' => 'The entity revision id this data is attached to',
      ];
    }

    $data_schema = [
      'description' => $description_current,
      'fields' => [
        'bundle' => [
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
        ],
        'deleted' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'A boolean indicating whether this data item has been deleted',
        ],
        'entity_id' => $id_schema,
        'revision_id' => $revision_id_schema,
        'langcode' => [
          'type' => 'varchar_ascii',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The language code for this data item.',
        ],
        'delta' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'The sequence number for this data item, used for multi-value fields',
        ],
      ],
      'primary key' => ['entity_id', 'deleted', 'delta', 'langcode'],
      'indexes' => [
        'bundle' => ['bundle'],
        'revision_id' => ['revision_id'],
      ],
    ];

    // Check that the schema does not include forbidden column names.
    $schema = $storage_definition->getSchema();
    $properties = $storage_definition->getPropertyDefinitions();
    $table_mapping = $this->getTableMapping($entity_type, [$storage_definition]);
    if (array_intersect(array_keys($schema['columns']), $table_mapping->getReservedColumns())) {
      throw new FieldException("Illegal field column names on {$storage_definition->getName()}");
    }

    // Add field columns.
    foreach ($schema['columns'] as $column_name => $attributes) {
      $real_name = $table_mapping->getFieldColumnName($storage_definition, $column_name);
      $data_schema['fields'][$real_name] = $attributes;
      // A dedicated table only contain rows for actual field values, and no
      // rows for entities where the field is empty. Thus, we can safely
      // enforce 'not null' on the columns for the field's required properties.
      // Fields can have dynamic properties, so we need to make sure that the
      // property is statically defined.
      if (isset($properties[$column_name])) {
        $data_schema['fields'][$real_name]['not null'] = $properties[$column_name]->isRequired();
      }
    }

    // Add indexes.
    foreach ($schema['indexes'] as $index_name => $columns) {
      $real_name = $this->getFieldIndexName($storage_definition, $index_name);
      foreach ($columns as $column_name) {
        // Indexes can be specified as either a column name or an array with
        // column name and length. Allow for either case.
        if (is_array($column_name)) {
          $data_schema['indexes'][$real_name][] = [
            $table_mapping->getFieldColumnName($storage_definition, $column_name[0]),
            $column_name[1],
          ];
        }
        else {
          $data_schema['indexes'][$real_name][] = $table_mapping->getFieldColumnName($storage_definition, $column_name);
        }
      }
    }

    // Add unique keys.
    foreach ($schema['unique keys'] as $index_name => $columns) {
      $real_name = $this->getFieldIndexName($storage_definition, $index_name);
      foreach ($columns as $column_name) {
        // Unique keys can be specified as either a column name or an array with
        // column name and length. Allow for either case.
        if (is_array($column_name)) {
          $data_schema['unique keys'][$real_name][] = [
            $table_mapping->getFieldColumnName($storage_definition, $column_name[0]),
            $column_name[1],
          ];
        }
        else {
          $data_schema['unique keys'][$real_name][] = $table_mapping->getFieldColumnName($storage_definition, $column_name);
        }
      }
    }

    // Add foreign keys.
    foreach ($schema['foreign keys'] as $specifier => $specification) {
      $real_name = $this->getFieldIndexName($storage_definition, $specifier);
      $data_schema['foreign keys'][$real_name]['table'] = $specification['table'];
      foreach ($specification['columns'] as $column_name => $referenced) {
        $sql_storage_column = $table_mapping->getFieldColumnName($storage_definition, $column_name);
        $data_schema['foreign keys'][$real_name]['columns'][$sql_storage_column] = $referenced;
      }
    }

    $dedicated_table_schema = [$table_mapping->getDedicatedDataTableName($storage_definition) => $data_schema];

    // If the entity type is revisionable, construct the revision table.
    if ($entity_type->isRevisionable()) {
      $revision_schema = $data_schema;
      $revision_schema['description'] = $description_revision;
      $revision_schema['primary key'] = ['entity_id', 'revision_id', 'deleted', 'delta', 'langcode'];
      $revision_schema['fields']['revision_id']['not null'] = TRUE;
      $revision_schema['fields']['revision_id']['description'] = 'The entity revision id this data is attached to';
      $dedicated_table_schema += [$table_mapping->getDedicatedRevisionTableName($storage_definition) => $revision_schema];
    }

    return $dedicated_table_schema;
  }

  /**
   * Gets the name to be used for the given entity index.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param string $index
   *   The index column name.
   *
   * @return string
   *   The index name.
   */
  protected function getEntityIndexName(ContentEntityTypeInterface $entity_type, $index) {
    return $entity_type->id() . '__' . $index;
  }

  /**
   * Generates an index name for a field data table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param string $index
   *   The name of the index.
   *
   * @return string
   *   A string containing a generated index name for a field data table that is
   *   unique among all other fields.
   */
  protected function getFieldIndexName(FieldStorageDefinitionInterface $storage_definition, $index) {
    return $storage_definition->getName() . '_' . $index;
  }

  /**
   * Checks whether a database table is non-existent or empty.
   *
   * Empty tables can be dropped and recreated without data loss.
   *
   * @param string $table_name
   *   The database table to check.
   *
   * @return bool
   *   TRUE if the table is empty, FALSE otherwise.
   */
  protected function isTableEmpty($table_name) {
    return !$this->database->schema()->tableExists($table_name) ||
      !$this->database->select($table_name)
        ->countQuery()
        ->range(0, 1)
        ->execute()
        ->fetchField();
  }

  /**
   * Compares schemas to check for changes in the column definitions.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   Current field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original field storage definition.
   *
   * @return bool
   *   Returns TRUE if there are schema changes in the column definitions.
   */
  protected function hasColumnChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    if ($storage_definition->getColumns() != $original->getColumns()) {
      // Base field definitions have schema data stored in the original
      // definition.
      return TRUE;
    }

    if (!$storage_definition->hasCustomStorage()) {
      $keys = array_flip($this->getColumnSchemaRelevantKeys());
      $definition_schema = $this->getSchemaFromStorageDefinition($storage_definition);
      foreach ($this->loadFieldSchemaData($original) as $table => $table_schema) {
        foreach ($table_schema['fields'] as $name => $spec) {
          $definition_spec = array_intersect_key($definition_schema[$table]['fields'][$name], $keys);
          $stored_spec = array_intersect_key($spec, $keys);
          if ($definition_spec != $stored_spec) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Returns a list of column schema keys affecting data storage.
   *
   * When comparing schema definitions, only changes in certain properties
   * actually affect how data is stored and thus, if applied, may imply data
   * manipulation.
   *
   * @return string[]
   *   An array of key names.
   */
  protected function getColumnSchemaRelevantKeys() {
    return ['type', 'size', 'length', 'unsigned'];
  }

  /**
   * Creates an index, dropping it if already existing.
   *
   * @param string $table
   *   The table name.
   * @param string $name
   *   The index name.
   * @param array $specifier
   *   The fields to index.
   * @param array $schema
   *   The table specification.
   *
   * @see \Drupal\Core\Database\Schema::addIndex()
   */
  protected function addIndex($table, $name, array $specifier, array $schema) {
    $schema_handler = $this->database->schema();
    $schema_handler->dropIndex($table, $name);
    $schema_handler->addIndex($table, $name, $specifier, $schema);
  }

  /**
   * Creates a unique key, dropping it if already existing.
   *
   * @param string $table
   *   The table name.
   * @param string $name
   *   The index name.
   * @param array $specifier
   *   The unique fields.
   *
   * @see \Drupal\Core\Database\Schema::addUniqueKey()
   */
  protected function addUniqueKey($table, $name, array $specifier) {
    $schema_handler = $this->database->schema();
    $schema_handler->dropUniqueKey($table, $name);
    $schema_handler->addUniqueKey($table, $name, $specifier);
  }

  /**
   * Typecasts values to the proper data type.
   *
   * MySQL PDO silently casts, e.g. FALSE and '' to 0, when inserting the value
   * into an integer column, but PostgreSQL PDO does not. Use the schema
   * information to correctly typecast the value.
   *
   * @param array $info
   *   An array describing the schema field info. See hook_schema() and
   *   https://www.drupal.org/node/146843 for details.
   * @param mixed $value
   *   The value to be converted.
   *
   * @return mixed
   *   The converted value.
   *
   * @internal
   *
   * @see hook_schema()
   * @see https://www.drupal.org/node/146843
   */
  public static function castValue(array $info, $value) {
    // Preserve legal NULL values.
    if (isset($value) || !empty($info['not null'])) {
      if ($info['type'] === 'int' || $info['type'] === 'serial') {
        return (int) $value;
      }
      elseif ($info['type'] === 'float') {
        return (float) $value;
      }
      return (string) $value;
    }

    return $value;
  }

}
