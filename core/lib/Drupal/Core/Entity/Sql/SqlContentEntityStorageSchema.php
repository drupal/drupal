<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema.
 */

namespace Drupal\Core\Entity\Sql;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Schema\FieldableEntityStorageSchemaInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines a schema handler that supports revisionable, translatable entities.
 */
class SqlContentEntityStorageSchema implements FieldableEntityStorageSchemaInterface {

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
    $this->entityType = $entity_type;
    $this->fieldStorageDefinitions = $entity_manager->getFieldStorageDefinitions($entity_type->id());
    $this->storage = $storage;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return
      $entity_type->getStorageClass() != $original->getStorageClass() ||
      $entity_type->getKeys() != $original->getKeys() ||
      $entity_type->isRevisionable() != $original->isRevisionable() ||
      $entity_type->isTranslatable() != $original->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return
      $storage_definition->hasCustomStorage() != $original->hasCustomStorage() ||
      $storage_definition->getSchema() != $original->getSchema() ||
      $storage_definition->isRevisionable() != $original->isRevisionable() ||
      $storage_definition->isTranslatable() != $original->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // If we're updating from NULL storage, then there's no stored data that
    // requires migration.
    // @todo Remove in https://www.drupal.org/node/2335879.
    $original_storage_class = $original->getStorageClass();
    $null_storage_class = 'Drupal\Core\Entity\ContentEntityNullStorage';
    if ($original_storage_class == $null_storage_class || is_subclass_of($original_storage_class, $null_storage_class)) {
      return FALSE;
    }

    return
      // If the original storage class is different, then there might be
      // existing entities in that storage even if the new storage's base
      // table is empty.
      // @todo Ask the old storage handler rather than assuming:
      //   https://www.drupal.org/node/2335879.
      $entity_type->getStorageClass() != $original_storage_class ||
      !$this->tableIsEmpty($this->storage->getBaseTable());
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldDataMigration(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    // If the base table is empty, there are no entities, and therefore, no
    // field data that we care about preserving.
    // @todo We might be returning TRUE here in cases where it would be safe
    //   to return FALSE (for example, if the field is in a dedicated table
    //   and that table is empty), and thereby preventing automatic updates
    //   that should be possible, but determining that requires refactoring
    //   SqlContentEntityStorage::_fieldSqlSchema(), and in the meantime,
    //   it's safer to return false positives than false negatives:
    //   https://www.drupal.org/node/1498720.
    return !$this->tableIsEmpty($this->storage->getBaseTable());
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $this->checkEntityType($entity_type);
    $schema_handler = $this->database->schema();
    $schema = $this->getEntitySchema($entity_type, TRUE);
    foreach ($schema as $table_name => $table_schema) {
      if (!$schema_handler->tableExists($table_name)) {
        $schema_handler->createTable($table_name, $table_schema);
      }
    }
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

    // If we have no data just recreate the entity schema from scratch.
    if (!$this->requiresEntityDataMigration($entity_type, $original)) {
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
    // Otherwise, throw an exception.
    else {
      throw new EntityStorageException(String::format('The SQL storage cannot change the schema for an existing entity type with data.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->checkEntityType($entity_type);
    $schema_handler = $this->database->schema();
    foreach ($this->getEntitySchemaTables() as $table_name) {
      if ($schema_handler->tableExists($table_name)) {
        $schema_handler->dropTable($table_name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    // @todo Move implementation from
    //   SqlContentEntityStorage::onFieldStorageDefinitionCreate()
    //   into here: https://www.drupal.org/node/1498720
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    // @todo Move implementation from
    //   SqlContentEntityStorage::onFieldStorageDefinitionUpdate()
    //   into here: https://www.drupal.org/node/1498720
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    // @todo Move implementation from
    //   SqlContentEntityStorage::onFieldStorageDefinitionDelete()
    //   into here: https://www.drupal.org/node/1498720
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
      throw new EntityStorageException(String::format('Unsupported entity type @id', array('@id' => $entity_type->id())));
    }
    return TRUE;
  }

  /**
   * Returns the entity schema for the specified entity type.
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
    $entity_type_id = $entity_type->id();

    if (!isset($this->schema[$entity_type_id]) || $reset) {
      // Initialize the table schema.
      $tables = $this->getEntitySchemaTables();
      $schema[$tables['base_table']] = $this->initializeBaseTable();
      if (isset($tables['revision_table'])) {
        $schema[$tables['revision_table']] = $this->initializeRevisionTable();
      }
      if (isset($tables['data_table'])) {
        $schema[$tables['data_table']] = $this->initializeDataTable();
      }
      if (isset($tables['revision_data_table'])) {
        $schema[$tables['revision_data_table']] = $this->initializeRevisionDataTable();
      }

      $table_mapping = $this->storage->getTableMapping();
      foreach ($table_mapping->getTableNames() as $table_name) {
        // Add the schema from field definitions.
        foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
          $column_names = $table_mapping->getColumnNames($field_name);
          $this->addFieldSchema($schema[$table_name], $field_name, $column_names);
        }

        // Add the schema for extra fields.
        foreach ($table_mapping->getExtraColumns($table_name) as $column_name) {
          if ($column_name == 'default_langcode') {
            $this->addDefaultLangcodeSchema($schema[$table_name]);
          }
        }
      }

      // Process tables after having gathered field information.
      $this->processBaseTable($schema[$tables['base_table']]);
      if (isset($tables['revision_table'])) {
        $this->processRevisionTable($schema[$tables['revision_table']]);
      }
      if (isset($tables['data_table'])) {
        $this->processDataTable($schema[$tables['data_table']]);
      }
      if (isset($tables['revision_data_table'])) {
        $this->processRevisionDataTable($schema[$tables['revision_data_table']]);
      }

      $this->schema[$entity_type_id] = $schema;
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
   * Returns the schema for a single field definition.
   *
   * @param array $schema
   *   The table schema to add the field schema to, passed by reference.
   * @param string $field_name
   *   The name of the field.
   * @param string[] $column_mapping
   *   A mapping of field column names to database column names.
   */
  protected function addFieldSchema(array &$schema, $field_name, array $column_mapping) {
    $field_schema = $this->fieldStorageDefinitions[$field_name]->getSchema();
    $field_description = $this->fieldStorageDefinitions[$field_name]->getDescription();

    foreach ($column_mapping as $field_column_name => $schema_field_name) {
      $column_schema = $field_schema['columns'][$field_column_name];

      $schema['fields'][$schema_field_name] = $column_schema;
      $schema['fields'][$schema_field_name]['description'] = $field_description;
      // Only entity keys are required.
      $keys = $this->entityType->getKeys() + array('langcode' => 'langcode');
      // The label is an entity key, but label fields are not necessarily
      // required.
      // Because entity ID and revision ID are both serial fields in the base
      // and revision table respectively, the revision ID is not known yet, when
      // inserting data into the base table. Instead the revision ID in the base
      // table is updated after the data has been inserted into the revision
      // table. For this reason the revision ID field cannot be marked as NOT
      // NULL.
      unset($keys['label'], $keys['revision']);
      // Key fields may not be NULL.
      if (in_array($field_name, $keys)) {
        $schema['fields'][$schema_field_name]['not null'] = TRUE;
      }
    }

    if (!empty($field_schema['indexes'])) {
      $indexes = $this->getFieldIndexes($field_name, $field_schema, $column_mapping);
      $schema['indexes'] = array_merge($schema['indexes'], $indexes);
    }

    if (!empty($field_schema['unique keys'])) {
      $unique_keys = $this->getFieldUniqueKeys($field_name, $field_schema, $column_mapping);
      $schema['unique keys'] = array_merge($schema['unique keys'], $unique_keys);
    }

    if (!empty($field_schema['foreign keys'])) {
      $foreign_keys = $this->getFieldForeignKeys($field_name, $field_schema, $column_mapping);
      $schema['foreign keys'] = array_merge($schema['foreign keys'], $foreign_keys);
    }
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
   * @param string $key
   *   The key of the field.
   *
   * @return string
   *   The field identifier name.
   */
  protected function getFieldSchemaIdentifierName($entity_type_id, $field_name, $key) {
    $real_key = "{$entity_type_id}_field__{$field_name}__{$key}";
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
   * Returns the schema for the 'default_langcode' metadata field.
   *
   * @param array $schema
   *   The table schema to add the field schema to, passed by reference.
   *
   * @return array
   *   A schema field array for the 'default_langcode' metadata field.
   */
  protected function addDefaultLangcodeSchema(&$schema) {
    $schema['fields']['default_langcode'] =  array(
      'description' => 'Boolean indicating whether field values are in the default entity language.',
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 1,
    );
  }

  /**
   * Initializes common information for a base table.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function initializeBaseTable() {
    $entity_type_id = $this->entityType->id();

    $schema = array(
      'description' => "The base table for $entity_type_id entities.",
      'primary key' => array($this->entityType->getKey('id')),
      'indexes' => array(),
      'foreign keys' => array(),
    );

    if ($this->entityType->hasKey('revision')) {
      $revision_key = $this->entityType->getKey('revision');
      $key_name = $this->getEntityIndexName($revision_key);
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
   * @return array
   *   A partial schema array for the revision table.
   */
  protected function initializeRevisionTable() {
    $entity_type_id = $this->entityType->id();
    $id_key = $this->entityType->getKey('id');
    $revision_key = $this->entityType->getKey('revision');

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

    $schema['indexes'][$this->getEntityIndexName($id_key)] = array($id_key);

    $this->addTableDefaults($schema);

    return $schema;
  }

  /**
   * Initializes common information for a data table.
   *
   * @return array
   *   A partial schema array for the data table.
   */
  protected function initializeDataTable() {
    $entity_type_id = $this->entityType->id();
    $id_key = $this->entityType->getKey('id');

    $schema = array(
      'description' => "The data table for $entity_type_id entities.",
      // @todo Use the language entity key when https://drupal.org/node/2143729
      //   is in.
      'primary key' => array($id_key, 'langcode'),
      'indexes' => array(),
      'foreign keys' => array(
        $entity_type_id => array(
          'table' => $this->storage->getBaseTable(),
          'columns' => array($id_key => $id_key),
        ),
      ),
    );

    if ($this->entityType->hasKey('revision')) {
      $key = $this->entityType->getKey('revision');
      $schema['indexes'][$this->getEntityIndexName($key)] = array($key);
    }

    $this->addTableDefaults($schema);

    return $schema;
  }

  /**
   * Initializes common information for a revision data table.
   *
   * @return array
   *   A partial schema array for the revision data table.
   */
  protected function initializeRevisionDataTable() {
    $entity_type_id = $this->entityType->id();
    $id_key = $this->entityType->getKey('id');
    $revision_key = $this->entityType->getKey('revision');

    $schema = array(
      'description' => "The revision data table for $entity_type_id entities.",
      // @todo Use the language entity key when https://drupal.org/node/2143729
      //   is in.
      'primary key' => array($revision_key, 'langcode'),
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
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processBaseTable(array &$schema) {
    $this->processIdentifierSchema($schema, $this->entityType->getKey('id'));
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processRevisionTable(array &$schema) {
    $this->processIdentifierSchema($schema, $this->entityType->getKey('revision'));
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processDataTable(array &$schema) {
  }

  /**
   * Processes the gathered schema for a base table.
   *
   * @param array $schema
   *   The table schema, passed by reference.
   *
   * @return array
   *   A partial schema array for the base table.
   */
  protected function processRevisionDataTable(array &$schema) {
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
    unset($schema['fields'][$key]['default']);
  }

  /**
   * Returns the name to be used for the given entity index.
   *
   * @param string $index
   *   The index column name.
   *
   * @return string
   *   The index name.
   */
  protected function getEntityIndexName($index) {
    return $this->entityType->id() . '__' . $index;
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
  protected function tableIsEmpty($table_name) {
    return !$this->database->schema()->tableExists($table_name) ||
      !$this->database->select($table_name)
        ->countQuery()
        ->range(0, 1)
        ->execute()
        ->fetchField();
  }

}
