<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema.
 */

namespace Drupal\Core\Entity\Sql;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Defines a schema handler that supports revisionable, translatable entities.
 *
 * Entity types may extend this class and optimize the generated schema for all
 * entity base tables by overriding getEntitySchema() for cross-field
 * optimizations and getSharedTableFieldSchema() for optimizations applying to
 * a single field.
 */
class SqlContentEntityStorageSchema implements DynamicallyFieldableEntityStorageSchemaInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * The original storage field definitions for this entity type. Used during
   * field schema updates.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $originalDefinitions;

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
   * Constructs a SqlContentEntityStorageSchema.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage
   *   The storage of the entity type. This must be an SQL-based storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(EntityManagerInterface $entity_manager, ContentEntityTypeInterface $entity_type, SqlContentEntityStorage $storage, Connection $database) {
    $this->entityManager = $entity_manager;
    $this->entityType = $entity_type;
    $this->fieldStorageDefinitions = $entity_manager->getFieldStorageDefinitions($entity_type->id());
    $this->storage = $storage;
    $this->database = $database;
  }

  /**
   * Returns the keyvalue collection for tracking the installed schema.
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
   * {@inheritdoc}
   */
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return
      $this->hasSharedTableStructureChange($entity_type, $original) ||
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
    return
      $entity_type->isRevisionable() != $original->isRevisionable() ||
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
    return
      $entity_type->getBaseTable() != $original->getBaseTable() ||
      $entity_type->getDataTable() != $original->getDataTable() ||
      $entity_type->getRevisionTable() != $original->getRevisionTable() ||
      $entity_type->getRevisionDataTable() != $original->getRevisionDataTable();
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $table_mapping = $this->storage->getTableMapping();

    if (
      $storage_definition->hasCustomStorage() != $original->hasCustomStorage() ||
      $storage_definition->getSchema() != $original->getSchema() ||
      $storage_definition->isRevisionable() != $original->isRevisionable() ||
      $table_mapping->allowsSharedTableStorage($storage_definition) != $table_mapping->allowsSharedTableStorage($original) ||
      $table_mapping->requiresDedicatedTableStorage($storage_definition) != $table_mapping->requiresDedicatedTableStorage($original)
    ) {
      return TRUE;
    }

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      return $this->getDedicatedTableSchema($storage_definition) != $this->loadFieldSchemaData($original);
    }
    elseif ($table_mapping->allowsSharedTableStorage($storage_definition)) {
      $field_name = $storage_definition->getName();
      $schema = array();
      foreach (array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames()) as $table_name) {
        if (in_array($field_name, $table_mapping->getFieldNames($table_name))) {
          $column_names = $table_mapping->getColumnNames($storage_definition->getName());
          $schema[$table_name] = $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names);
        }
      }
      return $schema != $this->loadFieldSchemaData($original);
    }
    else {
      // The field has custom storage, so we don't know if a schema change is
      // needed or not, but since per the initial checks earlier in this
      // function, nothing about the definition changed that we manage, we
      // return FALSE.
      return FALSE;
    }
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

    // Use the original entity type since the storage has not been updated.
    $original_storage = $this->entityManager->createHandlerInstance($original_storage_class, $original);
    return $original_storage->hasData();
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
    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type->id());
    $table_mapping = $this->storage->getTableMapping($field_storage_definitions);
    foreach ($field_storage_definitions as $field_storage_definition) {
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
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $this->checkEntityType($entity_type);
    $this->checkEntityType($original);

    // If no schema changes are needed, we don't need to do anything.
    if (!$this->requiresEntityStorageSchemaChanges($entity_type, $original)) {
      return;
    }

    // If a migration is required, we can't proceed.
    if ($this->requiresEntityDataMigration($entity_type, $original)) {
      throw new EntityStorageException(SafeMarkup::format('The SQL storage cannot change the schema for an existing entity type with data.'));
    }

    // If we have no data just recreate the entity schema from scratch.
    if ($this->isTableEmpty($this->storage->getBaseTable())) {
      if ($this->database->supportsTransactionalDDL()) {
        // If the database supports transactional DDL, we can go ahead and rely
        // on it. If not, we will have to rollback manually if something fails.
        $transaction = $this->database->startTransaction();
      }
      try {
        $this->onEntityTypeDelete($original);
        $this->onEntityTypeCreate($entity_type);
      }
      catch (\Exception $e) {
        if ($this->database->supportsTransactionalDDL()) {
          $transaction->rollback();
        }
        else {
          // Recreate original schema.
          $this->onEntityTypeCreate($original);
        }
        throw $e;
      }
    }
    else {
      $schema_handler = $this->database->schema();

      // Drop original indexes and unique keys.
      foreach ($this->loadEntitySchemaData($entity_type) as $table_name => $schema) {
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
      }

      // Create new indexes and unique keys.
      $entity_schema = $this->getEntitySchema($entity_type, TRUE);
      foreach ($this->getEntitySchemaData($entity_type, $entity_schema) as $table_name => $schema) {
        if (!empty($schema['indexes'])) {
          foreach ($schema['indexes'] as $name => $specifier) {
            $schema_handler->addIndex($table_name, $name, $specifier);
          }
        }
        if (!empty($schema['unique keys'])) {
          foreach ($schema['unique keys'] as $name => $specifier) {
            $schema_handler->addUniqueKey($table_name, $name, $specifier);
          }
        }
      }

      // Store the updated entity schema.
      $this->saveEntitySchemaData($entity_type, $entity_schema);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->checkEntityType($entity_type);
    $schema_handler = $this->database->schema();
    $actual_definition = $this->entityManager->getDefinition($entity_type->id());
    // @todo Instead of switching the wrapped entity type, we should be able to
    //   instantiate a new table mapping for each entity type definition. See
    //   https://www.drupal.org/node/2274017.
    $this->storage->setEntityType($entity_type);

    // Delete entity tables.
    foreach ($this->getEntitySchemaTables() as $table_name) {
      if ($schema_handler->tableExists($table_name)) {
        $schema_handler->dropTable($table_name);
      }
    }

    // Delete dedicated field tables.
    $field_storage_definitions = $this->entityManager->getLastInstalledFieldStorageDefinitions($entity_type->id());
    $this->originalDefinitions = $field_storage_definitions;
    $table_mapping = $this->storage->getTableMapping($field_storage_definitions);
    foreach ($field_storage_definitions as $field_storage_definition) {
      if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
        $this->deleteDedicatedTableSchema($field_storage_definition);
      }
    }
    $this->originalDefinitions = NULL;

    $this->storage->setEntityType($actual_definition);

    // Delete the entity schema.
    $this->deleteEntitySchemaData($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $this->performFieldSchemaOperation('create', $storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    // Store original definitions so that switching between shared and dedicated
    // field table layout works.
    $this->originalDefinitions = $this->fieldStorageDefinitions;
    $this->originalDefinitions[$original->getName()] = $original;
    $this->performFieldSchemaOperation('update', $storage_definition, $original);
    $this->originalDefinitions = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    // Only configurable fields currently support purging, so prevent deletion
    // of ones we can't purge if they have existing data.
    // @todo Add purging to all fields: https://www.drupal.org/node/2282119.
    try {
      if (!($storage_definition instanceof FieldStorageConfigInterface) && $this->storage->countFieldData($storage_definition, TRUE)) {
        throw new FieldStorageDefinitionUpdateForbiddenException('Unable to delete a field with data that cannot be purged.');
      }
    }
    catch (DatabaseException $e) {
      // This may happen when changing field storage schema, since we are not
      // able to use a table mapping matching the passed storage definition.
      // @todo Revisit this once we are able to instantiate the table mapping
      //   properly. See https://www.drupal.org/node/2274017.
      return;
    }

    // Retrieve a table mapping which contains the deleted field still.
    $table_mapping = $this->storage->getTableMapping(
      $this->entityManager->getLastInstalledFieldStorageDefinitions($this->entityType->id())
    );
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

    // @todo Remove when finalizePurge() is invoked from the outside for all
    //   fields: https://www.drupal.org/node/2282119.
    if (!($storage_definition instanceof FieldStorageConfigInterface)) {
      $this->performFieldSchemaOperation('delete', $storage_definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {
    $this->performFieldSchemaOperation('delete', $storage_definition);
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
      throw new EntityStorageException(SafeMarkup::format('Unsupported entity type @id', array('@id' => $entity_type->id())));
    }
    return TRUE;
  }

  /**
   * Returns the entity schema for the specified entity type.
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
   *
   * @throws \Drupal\Core\Field\FieldException
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $this->checkEntityType($entity_type);
    $entity_type_id = $entity_type->id();

    if (!isset($this->schema[$entity_type_id]) || $reset) {
      // Back up the storage definition and replace it with the passed one.
      // @todo Instead of switching the wrapped entity type, we should be able
      //   to instantiate a new table mapping for each entity type definition.
      //   See https://www.drupal.org/node/2274017.
      $actual_definition = $this->entityManager->getDefinition($entity_type_id);
      $this->storage->setEntityType($entity_type);

      // Prepare basic information about the entity type.
      $tables = $this->getEntitySchemaTables();

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
      $table_mapping = $this->storage->getTableMapping();
      $table_names = array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames());
      $storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type_id);
      foreach ($table_names as $table_name) {
        if (!isset($schema[$table_name])) {
          $schema[$table_name] = array();
        }
        foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
          if (!isset($storage_definitions[$field_name])) {
            throw new FieldException(SafeMarkup::format('Field storage definition for "@field_name" could not be found.', array('@field_name' => $field_name)));
          }
          // Add the schema for base field definitions.
          elseif ($table_mapping->allowsSharedTableStorage($storage_definitions[$field_name])) {
            $column_names = $table_mapping->getColumnNames($field_name);
            $storage_definition = $storage_definitions[$field_name];
            $schema[$table_name] = array_merge_recursive($schema[$table_name], $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names));
          }
        }
      }

      // Process tables after having gathered field information.
      $this->processBaseTable($entity_type, $schema[$tables['base_table']]);
      if (isset($tables['revision_table'])) {
        $this->processRevisionTable($entity_type, $schema[$tables['revision_table']]);
      }
      if (isset($tables['data_table'])) {
        $this->processDataTable($entity_type, $schema[$tables['data_table']]);
      }
      if (isset($tables['revision_data_table'])) {
        $this->processRevisionDataTable($entity_type, $schema[$tables['revision_data_table']]);
      }

      $this->schema[$entity_type_id] = $schema;

      // Restore the actual definition.
      $this->storage->setEntityType($actual_definition);
    }

    return $this->schema[$entity_type_id];
  }

  /**
   * Gets a list of entity type tables.
   *
   * @return array
   *   A list of entity type tables, keyed by table key.
   */
  protected function getEntitySchemaTables() {
    return array_filter(array(
      'base_table' => $this->storage->getBaseTable(),
      'revision_table' => $this->storage->getRevisionTable(),
      'data_table' => $this->storage->getDataTable(),
      'revision_data_table' => $this->storage->getRevisionDataTable(),
    ));
  }

  /**
   * Returns entity schema definitions for index and key definitions.
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
    $schema_data = array();
    $entity_type_id = $entity_type->id();
    $keys = array('indexes', 'unique keys');
    $unused_keys = array_flip(array('description', 'fields', 'foreign keys'));

    foreach ($schema as $table_name => $table_schema) {
      $table_schema = array_diff_key($table_schema, $unused_keys);
      foreach ($keys as $key) {
        // Exclude data generated from field storage definitions, we will check
        // that separately.
        if (!empty($table_schema[$key])) {
          $data_keys = array_keys($table_schema[$key]);
          $entity_keys = array_filter($data_keys, function ($key) use ($entity_type_id) {
            return strpos($key, $entity_type_id . '_field__') !== 0;
          });
          $table_schema[$key] = array_intersect_key($table_schema[$key], array_flip($entity_keys));
        }
      }
      $schema_data[$table_name] = array_filter($table_schema);
    }

    return $schema_data;
  }

  /**
   * Returns an index schema array for a given field.
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
   * Returns a unique key schema array for a given field.
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
   * Returns field schema data for the given key.
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
    $data = array();

    foreach ($field_schema[$schema_key] as $key => $columns) {
      // To avoid clashes with entity-level indexes or unique keys we use
      // "{$entity_type_id}_field__" as a prefix instead of just
      // "{$entity_type_id}__". We additionally namespace the specifier by the
      // field name to avoid clashes when multiple fields of the same type are
      // added to an entity type.
      $entity_type_id = $this->entityType->id();
      $real_key = $this->getFieldSchemaIdentifierName($entity_type_id, $field_name, $key);
      foreach ($columns as $column) {
        // Allow for indexes and unique keys to specified as an array of column
        // name and length.
        if (is_array($column)) {
          list($column_name, $length) = $column;
          $data[$real_key][] = array($column_mapping[$column_name], $length);
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
   * Returns field foreign keys.
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
    $foreign_keys = array();

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
    return $this->installedStorageSchema()->get($entity_type->id() . '.entity_schema_data', array());
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
    return $this->installedStorageSchema()->get($storage_definition->getTargetEntityTypeId() . '.field_schema_data.' . $storage_definition->getName(), array());
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

    $schema = array(
      'description' => "The base table for $entity_type_id entities.",
      'primary key' => array($entity_type->getKey('id')),
      'indexes' => array(),
      'foreign keys' => array(),
    );

    if ($entity_type->hasKey('revision')) {
      $revision_key = $entity_type->getKey('revision');
      $key_name = $this->getEntityIndexName($entity_type, $revision_key);
      $schema['unique keys'][$key_name] = array($revision_key);
      $schema['foreign keys'][$entity_type_id . '__revision'] = array(
        'table' => $this->storage->getRevisionTable(),
        'columns' => array($revision_key => $revision_key),
      );
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

    $schema = array(
      'description' => "The revision table for $entity_type_id entities.",
      'primary key' => array($revision_key),
      'indexes' => array(),
      'foreign keys' => array(
        $entity_type_id . '__revisioned' => array(
          'table' => $this->storage->getBaseTable(),
          'columns' => array($id_key => $id_key),
        ),
      ),
    );

    $schema['indexes'][$this->getEntityIndexName($entity_type, $id_key)] = array($id_key);

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

    $schema = array(
      'description' => "The data table for $entity_type_id entities.",
      'primary key' => array($id_key, $entity_type->getKey('langcode')),
      'indexes' => array(),
      'foreign keys' => array(
        $entity_type_id => array(
          'table' => $this->storage->getBaseTable(),
          'columns' => array($id_key => $id_key),
        ),
      ),
    );

    if ($entity_type->hasKey('revision')) {
      $key = $entity_type->getKey('revision');
      $schema['indexes'][$this->getEntityIndexName($entity_type, $key)] = array($key);
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

    $schema = array(
      'description' => "The revision data table for $entity_type_id entities.",
      'primary key' => array($revision_key, $entity_type->getKey('langcode')),
      'indexes' => array(),
      'foreign keys' => array(
        $entity_type_id => array(
          'table' => $this->storage->getBaseTable(),
          'columns' => array($id_key => $id_key),
        ),
        $entity_type_id . '__revision' => array(
          'table' => $this->storage->getRevisionTable(),
          'columns' => array($revision_key => $revision_key),
        )
      ),
    );

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
    $schema += array(
      'fields' => array(),
      'unique keys' => array(),
      'indexes' => array(),
      'foreign keys' => array(),
    );
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processBaseTable(ContentEntityTypeInterface $entity_type, array &$schema) {
    $this->processIdentifierSchema($schema, $entity_type->getKey('id'));
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processRevisionTable(ContentEntityTypeInterface $entity_type, array &$schema) {
    $this->processIdentifierSchema($schema, $entity_type->getKey('revision'));
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processDataTable(ContentEntityTypeInterface $entity_type, array &$schema) {
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processRevisionDataTable(ContentEntityTypeInterface $entity_type, array &$schema) {
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
  protected function performFieldSchemaOperation($operation, FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original = NULL) {
    $table_mapping = $this->storage->getTableMapping();
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
   */
  protected function createDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition) {
    $schema = $this->getDedicatedTableSchema($storage_definition);
    foreach ($schema as $name => $table) {
      // Check if the table exists because it might already have been
      // created as part of the earlier entity type update event.
      if (!$this->database->schema()->tableExists($name)) {
        $this->database->schema()->createTable($name, $table);
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
    $table_mapping = $this->storage->getTableMapping();
    $column_names = $table_mapping->getColumnNames($created_field_name);
    $schema_handler = $this->database->schema();
    $shared_table_names = array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames());

    // Iterate over the mapped table to find the ones that will host the created
    // field schema.
    $schema = array();
    foreach ($shared_table_names as $table_name) {
      foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
        if ($field_name == $created_field_name) {
          // Create field columns.
          $schema[$table_name] = $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names);
          if (!$only_save) {
            foreach ($schema[$table_name]['fields'] as $name => $specifier) {
              // Check if the field exists because it might already have been
              // created as part of the earlier entity type update event.
              if (!$schema_handler->fieldExists($table_name, $name)) {
                $schema_handler->addField($table_name, $name, $specifier);
              }
            }
            if (!empty($schema[$table_name]['indexes'])) {
              foreach ($schema[$table_name]['indexes'] as $name => $specifier) {
                // Check if the index exists because it might already have been
                // created as part of the earlier entity type update event.
                if (!$schema_handler->indexExists($table_name, $name)) {
                  $schema_handler->addIndex($table_name, $name, $specifier);
                }
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
  }

  /**
   * Deletes the schema for a field stored in a dedicated table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The storage definition of the field being deleted.
   */
  protected function deleteDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition) {
    // When switching from dedicated to shared field table layout we need need
    // to delete the field tables with their regular names. When this happens
    // original definitions will be defined.
    $deleted = !$this->originalDefinitions;
    $table_mapping = $this->storage->getTableMapping();
    $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $deleted);
    $this->database->schema()->dropTable($table_name);
    if ($this->entityType->isRevisionable()) {
      $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $deleted);
      $this->database->schema()->dropTable($revision_name);
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
    $deleted_field_name = $storage_definition->getName();
    $table_mapping = $this->storage->getTableMapping(
      $this->entityManager->getLastInstalledFieldStorageDefinitions($this->entityType->id())
    );
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
      if ($this->database->supportsTransactionalDDL()) {
        // If the database supports transactional DDL, we can go ahead and rely
        // on it. If not, we will have to rollback manually if something fails.
        $transaction = $this->database->startTransaction();
      }
      try {
        // Since there is no data we may be switching from a shared table schema
        // to a dedicated table schema, hence we should use the proper API.
        $this->performFieldSchemaOperation('delete', $original);
        $this->performFieldSchemaOperation('create', $storage_definition);
      }
      catch (\Exception $e) {
        if ($this->database->supportsTransactionalDDL()) {
          $transaction->rollback();
        }
        else {
          // Recreate tables.
          $this->performFieldSchemaOperation('create', $original);
        }
        throw $e;
      }
    }
    else {
      if ($storage_definition->getColumns() != $original->getColumns()) {
        throw new FieldStorageDefinitionUpdateForbiddenException("The SQL storage cannot change the schema for an existing field with data.");
      }
      // There is data, so there are no column changes. Drop all the prior
      // indexes and create all the new ones, except for all the priors that
      // exist unchanged.
      $table_mapping = $this->storage->getTableMapping();
      $table = $table_mapping->getDedicatedDataTableName($original);
      $revision_table = $table_mapping->getDedicatedRevisionTableName($original);

      $schema = $storage_definition->getSchema();
      $original_schema = $original->getSchema();

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
          $real_columns = array();
          foreach ($columns as $column_name) {
            // Indexes can be specified as either a column name or an array with
            // column name and length. Allow for either case.
            if (is_array($column_name)) {
              $real_columns[] = array(
                $table_mapping->getFieldColumnName($storage_definition, $column_name[0]),
                $column_name[1],
              );
            }
            else {
              $real_columns[] = $table_mapping->getFieldColumnName($storage_definition, $column_name);
            }
          }
          $this->database->schema()->addIndex($table, $real_name, $real_columns);
          $this->database->schema()->addIndex($revision_table, $real_name, $real_columns);
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
      if ($this->database->supportsTransactionalDDL()) {
        // If the database supports transactional DDL, we can go ahead and rely
        // on it. If not, we will have to rollback manually if something fails.
        $transaction = $this->database->startTransaction();
      }
      try {
        // Since there is no data we may be switching from a dedicated table
        // to a schema table schema, hence we should use the proper API.
        $this->performFieldSchemaOperation('delete', $original);
        $this->performFieldSchemaOperation('create', $storage_definition);
      }
      catch (\Exception $e) {
        if ($this->database->supportsTransactionalDDL()) {
          $transaction->rollback();
        }
        else {
          // Recreate original schema.
          $this->createSharedTableSchema($original);
        }
        throw $e;
      }
    }
    else {
      if ($storage_definition->getColumns() != $original->getColumns()) {
        throw new FieldStorageDefinitionUpdateForbiddenException("The SQL storage cannot change the schema for an existing field with data.");
      }

      $updated_field_name = $storage_definition->getName();
      $table_mapping = $this->storage->getTableMapping();
      $column_names = $table_mapping->getColumnNames($updated_field_name);
      $schema_handler = $this->database->schema();

      // Iterate over the mapped table to find the ones that host the deleted
      // field schema.
      $original_schema = $this->loadFieldSchemaData($original);
      $schema = array();
      foreach ($table_mapping->getTableNames() as $table_name) {
        foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
          if ($field_name == $updated_field_name) {
            $schema[$table_name] = $this->getSharedTableFieldSchema($storage_definition, $table_name, $column_names);

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
                $schema_handler->addIndex($table_name, $name, $specifier);
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
   * Returns the schema for a single field definition.
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
   *   Exception thrown if the schema contains reserved column names.
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = array();
    $field_schema = $storage_definition->getSchema();

    // Check that the schema does not include forbidden column names.
    if (array_intersect(array_keys($field_schema['columns']), $this->storage->getTableMapping()->getReservedColumns())) {
      throw new FieldException(format_string('Illegal field column names on @field_name', array('@field_name' => $storage_definition->getName())));
    }

    $field_name = $storage_definition->getName();
    $base_table = $this->storage->getBaseTable();

    // A shared table contains rows for entities where the field is empty
    // (since other fields stored in the same table might not be empty), thus
    // the only columns that can be 'not null' are those for required
    // properties of required fields. However, even those would break in the
    // case where a new field is added to a table that contains existing rows.
    // For now, we only hardcode 'not null' to a couple "entity keys", in order
    // to keep their indexes optimized.
    // @todo Revisit once we have support for 'initial' in
    //   https://www.drupal.org/node/2346019.
    $not_null_keys = $this->entityType->getKeys();
    // Label fields are not necessarily required.
    unset($not_null_keys['label']);
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
    $schema['indexes'][$real_key] = array($size ? array($name, $size) : $name);
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
    $schema['unique keys'][$real_key] = array($name);
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
    $schema['foreign keys'][$real_key] = array(
      'table' => $foreign_table,
      'columns' => array($name => $foreign_column),
    );
  }

  /**
   * Returns the SQL schema for a dedicated table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   (optional) The entity type definition. Defaults to the one returned by
   *   the entity manager.
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
  protected function getDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition, ContentEntityTypeInterface $entity_type = NULL) {
    $description_current = "Data storage for {$storage_definition->getTargetEntityTypeId()} field {$storage_definition->getName()}.";
    $description_revision = "Revision archive storage for {$storage_definition->getTargetEntityTypeId()} field {$storage_definition->getName()}.";

    $id_definition = $this->fieldStorageDefinitions[$this->entityType->getKey('id')];
    if ($id_definition->getType() == 'integer') {
      $id_schema = array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The entity id this data is attached to',
      );
    }
    else {
      $id_schema = array(
        'type' => 'varchar_ascii',
        'length' => 128,
        'not null' => TRUE,
        'description' => 'The entity id this data is attached to',
      );
    }

    // Define the revision ID schema.
    if (!$this->entityType->isRevisionable()) {
      $revision_id_schema = $id_schema;
      $revision_id_schema['description'] = 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id';
    }
    elseif ($this->fieldStorageDefinitions[$this->entityType->getKey('revision')]->getType() == 'integer') {
      $revision_id_schema = array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The entity revision id this data is attached to',
      );
    }
    else {
      $revision_id_schema = array(
        'type' => 'varchar',
        'length' => 128,
        'not null' => TRUE,
        'description' => 'The entity revision id this data is attached to',
      );
    }

    $data_schema = array(
      'description' => $description_current,
      'fields' => array(
        'bundle' => array(
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
        ),
        'deleted' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'A boolean indicating whether this data item has been deleted'
        ),
        'entity_id' => $id_schema,
        'revision_id' => $revision_id_schema,
        'langcode' => array(
          'type' => 'varchar_ascii',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The language code for this data item.',
        ),
        'delta' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'The sequence number for this data item, used for multi-value fields',
        ),
      ),
      'primary key' => array('entity_id', 'deleted', 'delta', 'langcode'),
      'indexes' => array(
        'bundle' => array('bundle'),
        'revision_id' => array('revision_id'),
      ),
    );

    // Check that the schema does not include forbidden column names.
    $schema = $storage_definition->getSchema();
    $properties = $storage_definition->getPropertyDefinitions();
    $table_mapping = $this->storage->getTableMapping();
    if (array_intersect(array_keys($schema['columns']), $table_mapping->getReservedColumns())) {
      throw new FieldException(format_string('Illegal field column names on @field_name', array('@field_name' => $storage_definition->getName())));
    }

    // Add field columns.
    foreach ($schema['columns'] as $column_name => $attributes) {
      $real_name = $table_mapping->getFieldColumnName($storage_definition, $column_name);
      $data_schema['fields'][$real_name] = $attributes;
      // A dedicated table only contain rows for actual field values, and no
      // rows for entities where the field is empty. Thus, we can safely
      // enforce 'not null' on the columns for the field's required properties.
      $data_schema['fields'][$real_name]['not null'] = $properties[$column_name]->isRequired();
    }

    // Add indexes.
    foreach ($schema['indexes'] as $index_name => $columns) {
      $real_name = $this->getFieldIndexName($storage_definition, $index_name);
      foreach ($columns as $column_name) {
        // Indexes can be specified as either a column name or an array with
        // column name and length. Allow for either case.
        if (is_array($column_name)) {
          $data_schema['indexes'][$real_name][] = array(
            $table_mapping->getFieldColumnName($storage_definition, $column_name[0]),
            $column_name[1],
          );
        }
        else {
          $data_schema['indexes'][$real_name][] = $table_mapping->getFieldColumnName($storage_definition, $column_name);
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

    $dedicated_table_schema = array($table_mapping->getDedicatedDataTableName($storage_definition) => $data_schema);

    // If the entity type is revisionable, construct the revision table.
    $entity_type = $entity_type ?: $this->entityType;
    if ($entity_type->isRevisionable()) {
      $revision_schema = $data_schema;
      $revision_schema['description'] = $description_revision;
      $revision_schema['primary key'] = array('entity_id', 'revision_id', 'deleted', 'delta', 'langcode');
      $revision_schema['fields']['revision_id']['not null'] = TRUE;
      $revision_schema['fields']['revision_id']['description'] = 'The entity revision id this data is attached to';
      $dedicated_table_schema += array($table_mapping->getDedicatedRevisionTableName($storage_definition) => $revision_schema);
    }

    return $dedicated_table_schema;
  }

  /**
   * Returns the name to be used for the given entity index.
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

}
