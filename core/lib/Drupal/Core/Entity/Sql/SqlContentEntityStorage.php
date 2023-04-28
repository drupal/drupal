<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Utility\Error;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A content entity database storage implementation.
 *
 * This class can be used as-is by most content entity types. Entity types
 * requiring special handling can extend the class.
 *
 * The class uses \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema
 * internally in order to automatically generate the database schema based on
 * the defined base fields. Entity types can override the schema handler to
 * customize the generated schema; e.g., to add additional indexes.
 *
 * @ingroup entity_api
 */
class SqlContentEntityStorage extends ContentEntityStorageBase implements SqlEntityStorageInterface, DynamicallyFieldableEntityStorageSchemaInterface, EntityBundleListenerInterface {

  /**
   * The entity type's field storage definitions.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected $fieldStorageDefinitions;

  /**
   * The mapping of field columns to SQL tables.
   *
   * @var \Drupal\Core\Entity\Sql\TableMappingInterface
   */
  protected $tableMapping;

  /**
   * Name of entity's revision database table field, if it supports revisions.
   *
   * Has the value FALSE if this entity does not use revisions.
   *
   * @var string
   */
  protected $revisionKey = FALSE;

  /**
   * The entity langcode key.
   *
   * @var string|bool
   */
  protected $langcodeKey = FALSE;

  /**
   * The default language entity key.
   *
   * @var string
   */
  protected $defaultLangcodeKey = FALSE;

  /**
   * The base table of the entity.
   *
   * @var string
   */
  protected $baseTable;

  /**
   * The table that stores revisions, if the entity supports revisions.
   *
   * @var string
   */
  protected $revisionTable;

  /**
   * The table that stores properties, if the entity has multilingual support.
   *
   * @var string
   */
  protected $dataTable;

  /**
   * The table that stores revision field data if the entity supports revisions.
   *
   * @var string
   */
  protected $revisionDataTable;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type's storage schema object.
   *
   * @var \Drupal\Core\Entity\Schema\EntityStorageSchemaInterface
   */
  protected $storageSchema;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Whether this storage should use the temporary table mapping.
   *
   * @var bool
   */
  protected $temporary = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a SqlContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend to be used.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $entity_field_manager, $cache, $memory_cache, $entity_type_bundle_info);
    $this->database = $database;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityType = $this->entityTypeManager->getActiveDefinition($entity_type->id());
    $this->fieldStorageDefinitions = $this->entityFieldManager->getActiveFieldStorageDefinitions($entity_type->id());

    $this->initTableLayout();
  }

  /**
   * Initializes table name variables.
   */
  protected function initTableLayout() {
    // Reset table field values to ensure changes in the entity type definition
    // are correctly reflected in the table layout.
    $this->tableMapping = NULL;
    $this->revisionKey = NULL;
    $this->revisionTable = NULL;
    $this->dataTable = NULL;
    $this->revisionDataTable = NULL;

    $table_mapping = $this->getTableMapping();
    $this->baseTable = $table_mapping->getBaseTable();
    $revisionable = $this->entityType->isRevisionable();
    if ($revisionable) {
      $this->revisionKey = $this->entityType->getKey('revision') ?: 'revision_id';
      $this->revisionTable = $table_mapping->getRevisionTable();
    }
    $translatable = $this->entityType->isTranslatable();
    if ($translatable) {
      $this->dataTable = $table_mapping->getDataTable();
      $this->langcodeKey = $this->entityType->getKey('langcode');
      $this->defaultLangcodeKey = $this->entityType->getKey('default_langcode');
    }
    if ($revisionable && $translatable) {
      $this->revisionDataTable = $table_mapping->getRevisionDataTable();
    }
  }

  /**
   * Gets the base table name.
   *
   * @return string
   *   The table name.
   */
  public function getBaseTable() {
    return $this->baseTable;
  }

  /**
   * Gets the revision table name.
   *
   * @return string|false
   *   The table name or FALSE if it is not available.
   */
  public function getRevisionTable() {
    return $this->revisionTable;
  }

  /**
   * Gets the data table name.
   *
   * @return string|false
   *   The table name or FALSE if it is not available.
   */
  public function getDataTable() {
    return $this->dataTable;
  }

  /**
   * Gets the revision data table name.
   *
   * @return string|false
   *   The table name or FALSE if it is not available.
   */
  public function getRevisionDataTable() {
    return $this->revisionDataTable;
  }

  /**
   * Gets the entity type's storage schema object.
   *
   * @return \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema
   *   The schema object.
   */
  protected function getStorageSchema() {
    if (!isset($this->storageSchema)) {
      $class = $this->entityType->getHandlerClass('storage_schema') ?: 'Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema';
      $this->storageSchema = new $class($this->entityTypeManager, $this->entityType, $this, $this->database, $this->entityFieldManager);
    }
    return $this->storageSchema;
  }

  /**
   * Updates the wrapped entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The update entity type.
   *
   * @internal Only to be used internally by Entity API. Expected to be
   *   removed by https://www.drupal.org/node/2274017.
   */
  public function setEntityType(EntityTypeInterface $entity_type) {
    if ($this->entityType->id() == $entity_type->id()) {
      $this->entityType = $entity_type;
      $this->initTableLayout();
    }
    else {
      throw new EntityStorageException("Unsupported entity type {$entity_type->id()}");
    }
  }

  /**
   * Updates the internal list of field storage definitions.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storage_definitions
   *   An array of field storage definitions.
   *
   * @internal Only to be used internally by Entity API.
   */
  public function setFieldStorageDefinitions(array $field_storage_definitions) {
    foreach ($field_storage_definitions as $field_storage_definition) {
      if ($field_storage_definition->getTargetEntityTypeId() !== $this->entityType->id()) {
        throw new EntityStorageException("Unsupported entity type {$field_storage_definition->getTargetEntityTypeId()}");
      }
    }

    $this->fieldStorageDefinitions = $field_storage_definitions;
  }

  /**
   * Sets the wrapped table mapping definition.
   *
   * @param \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping
   *   The table mapping.
   *
   * @internal Only to be used internally by Entity API. Expected to be removed
   *   by https://www.drupal.org/node/2554235.
   */
  public function setTableMapping(TableMappingInterface $table_mapping) {
    $this->tableMapping = $table_mapping;

    $this->baseTable = $table_mapping->getBaseTable();
    $this->revisionTable = $table_mapping->getRevisionTable();
    $this->dataTable = $table_mapping->getDataTable();
    $this->revisionDataTable = $table_mapping->getRevisionDataTable();
  }

  /**
   * Changes the temporary state of the storage.
   *
   * @param bool $temporary
   *   Whether to use a temporary table mapping or not.
   *
   * @internal Only to be used internally by Entity API.
   */
  public function setTemporary($temporary) {
    $this->temporary = $temporary;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableMapping(array $storage_definitions = NULL) {
    // If a new set of field storage definitions is passed, for instance when
    // comparing old and new storage schema, we compute the table mapping
    // without caching.
    if ($storage_definitions) {
      return $this->getCustomTableMapping($this->entityType, $storage_definitions);
    }

    // If we are using our internal storage definitions, which is our main use
    // case, we can statically cache the computed table mapping.
    if (!isset($this->tableMapping)) {
      $this->tableMapping = $this->getCustomTableMapping($this->entityType, $this->fieldStorageDefinitions);
    }

    return $this->tableMapping;
  }

  /**
   * Gets a table mapping for the specified entity type and storage definitions.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   An entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   An array of field storage definitions to be used to compute the table
   *   mapping.
   * @param string $prefix
   *   (optional) A prefix to be used by all the tables of this mapping.
   *   Defaults to an empty string.
   *
   * @return \Drupal\Core\Entity\Sql\TableMappingInterface
   *   A table mapping object for the entity's tables.
   *
   * @internal
   */
  public function getCustomTableMapping(ContentEntityTypeInterface $entity_type, array $storage_definitions, $prefix = '') {
    $prefix = $prefix ?: ($this->temporary ? 'tmp_' : '');
    return DefaultTableMapping::create($entity_type, $storage_definitions, $prefix);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    // Attempt to load entities from the persistent cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromPersistentCache($ids);

    // Load any remaining entities from the database.
    if ($entities_from_storage = $this->getFromStorage($ids)) {
      $this->invokeStorageLoadHook($entities_from_storage);
      $this->setPersistentCache($entities_from_storage);
    }

    return $entities_from_cache + $entities_from_storage;
  }

  /**
   * Gets entities from the storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return all entities
   *   when NULL.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the storage.
   */
  protected function getFromStorage(array $ids = NULL) {
    $entities = [];

    if (!empty($ids)) {
      // Sanitize IDs. Before feeding ID array into buildQuery, check whether
      // it is empty as this would load all entities.
      $ids = $this->cleanIds($ids);
    }

    if ($ids === NULL || $ids) {
      // Build and execute the query.
      $query_result = $this->buildQuery($ids)->execute();
      $records = $query_result->fetchAllAssoc($this->idKey);

      // Map the loaded records into entity objects and according fields.
      if ($records) {
        $entities = $this->mapFromStorageRecords($records);
      }
    }

    return $entities;
  }

  /**
   * Maps from storage records to entity objects, and attaches fields.
   *
   * @param array $records
   *   Associative array of query results, keyed on the entity ID or revision
   *   ID.
   * @param bool $load_from_revision
   *   (optional) Flag to indicate whether revisions should be loaded or not.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = FALSE) {
    if (!$records) {
      return [];
    }

    // Get the names of the fields that are stored in the base table and, if
    // applicable, the revision table. Other entity data will be loaded in
    // loadFromSharedTables() and loadFromDedicatedTables().
    $field_names = $this->tableMapping->getFieldNames($this->baseTable);
    if ($this->revisionTable) {
      $field_names = array_unique(array_merge($field_names, $this->tableMapping->getFieldNames($this->revisionTable)));
    }

    $values = [];
    foreach ($records as $id => $record) {
      $values[$id] = [];
      // Skip the item delta and item value levels (if possible) but let the
      // field assign the value as suiting. This avoids unnecessary array
      // hierarchies and saves memory here.
      foreach ($field_names as $field_name) {
        $field_columns = $this->tableMapping->getColumnNames($field_name);
        // Handle field types that store several properties.
        if (count($field_columns) > 1) {
          $definition_columns = $this->fieldStorageDefinitions[$field_name]->getColumns();
          foreach ($field_columns as $property_name => $column_name) {
            if (property_exists($record, $column_name)) {
              $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$property_name] = !empty($definition_columns[$property_name]['serialize']) ? unserialize($record->{$column_name}) : $record->{$column_name};
              unset($record->{$column_name});
            }
          }
        }
        // Handle field types that store only one property.
        else {
          $column_name = reset($field_columns);
          if (property_exists($record, $column_name)) {
            $columns = $this->fieldStorageDefinitions[$field_name]->getColumns();
            $column = reset($columns);
            $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT] = !empty($column['serialize']) ? unserialize($record->{$column_name}) : $record->{$column_name};
            unset($record->{$column_name});
          }
        }
      }

      // Handle additional record entries that are not provided by an entity
      // field, such as 'isDefaultRevision'.
      foreach ($record as $name => $value) {
        $values[$id][$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
      }
    }

    // Initialize translations array.
    $translations = array_fill_keys(array_keys($values), []);

    // Load values from shared and dedicated tables.
    $this->loadFromSharedTables($values, $translations, $load_from_revision);
    $this->loadFromDedicatedTables($values, $load_from_revision);

    $entities = [];
    foreach ($values as $id => $entity_values) {
      $bundle = $this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : NULL;
      // Turn the record into an entity class.
      $entity_class = $this->getEntityClass($bundle);
      $entities[$id] = new $entity_class($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));
    }

    return $entities;
  }

  /**
   * Loads values for fields stored in the shared data tables.
   *
   * @param array &$values
   *   Associative array of entities values, keyed on the entity ID or the
   *   revision ID.
   * @param array &$translations
   *   List of translations, keyed on the entity ID.
   * @param bool $load_from_revision
   *   Flag to indicate whether revisions should be loaded or not.
   */
  protected function loadFromSharedTables(array &$values, array &$translations, $load_from_revision) {
    $record_key = !$load_from_revision ? $this->idKey : $this->revisionKey;
    if ($this->dataTable) {
      // If a revision table is available, we need all the properties of the
      // latest revision. Otherwise we fall back to the data table.
      $table = $this->revisionDataTable ?: $this->dataTable;
      $alias = $this->revisionDataTable ? 'revision' : 'data';
      $query = $this->database->select($table, $alias, ['fetch' => \PDO::FETCH_ASSOC])
        ->fields($alias)
        ->condition($alias . '.' . $record_key, array_keys($values), 'IN')
        ->orderBy($alias . '.' . $record_key);

      $table_mapping = $this->getTableMapping();
      if ($this->revisionDataTable) {
        // Find revisioned fields that are not entity keys. Exclude the langcode
        // key as the base table holds only the default language.
        $base_fields = array_diff($table_mapping->getFieldNames($this->baseTable), [$this->langcodeKey]);
        $revisioned_fields = array_diff($table_mapping->getFieldNames($this->revisionDataTable), $base_fields);

        // Find fields that are not revisioned or entity keys. Data fields have
        // the same value regardless of entity revision.
        $data_fields = array_diff($table_mapping->getFieldNames($this->dataTable), $revisioned_fields, $base_fields);
        // If there are no data fields then only revisioned fields are needed
        // else both data fields and revisioned fields are needed to map the
        // entity values.
        $all_fields = $revisioned_fields;
        if ($data_fields) {
          $all_fields = array_merge($revisioned_fields, $data_fields);
          $query->leftJoin($this->dataTable, 'data', "([revision].[$this->idKey] = [data].[$this->idKey] AND [revision].[$this->langcodeKey] = [data].[$this->langcodeKey])");
          $column_names = [];
          // Some fields can have more then one columns in the data table so
          // column names are needed.
          foreach ($data_fields as $data_field) {
            // \Drupal\Core\Entity\Sql\TableMappingInterface::getColumnNames()
            // returns an array keyed by property names so remove the keys
            // before array_merge() to avoid losing data with fields having the
            // same columns i.e. value.
            $column_names[] = array_values($table_mapping->getColumnNames($data_field));
          }
          $column_names = array_merge(...$column_names);
          $query->fields('data', $column_names);
        }

        // Get the revision IDs.
        $revision_ids = [];
        foreach ($values as $entity_values) {
          $revision_ids[] = $entity_values[$this->revisionKey][LanguageInterface::LANGCODE_DEFAULT];
        }
        $query->condition('revision.' . $this->revisionKey, $revision_ids, 'IN');
      }
      else {
        $all_fields = $table_mapping->getFieldNames($this->dataTable);
      }

      $result = $query->execute();
      foreach ($result as $row) {
        $id = $row[$record_key];

        // Field values in default language are stored with
        // LanguageInterface::LANGCODE_DEFAULT as key.
        $langcode = empty($row[$this->defaultLangcodeKey]) ? $row[$this->langcodeKey] : LanguageInterface::LANGCODE_DEFAULT;

        $translations[$id][$langcode] = TRUE;

        foreach ($all_fields as $field_name) {
          $storage_definition = $this->fieldStorageDefinitions[$field_name];
          $definition_columns = $storage_definition->getColumns();
          $columns = $table_mapping->getColumnNames($field_name);
          // Do not key single-column fields by property name.
          if (count($columns) == 1) {
            $column_name = reset($columns);
            $column_attributes = $definition_columns[key($columns)];
            $values[$id][$field_name][$langcode] = (!empty($column_attributes['serialize'])) ? unserialize($row[$column_name]) : $row[$column_name];
          }
          else {
            foreach ($columns as $property_name => $column_name) {
              $column_attributes = $definition_columns[$property_name];
              $values[$id][$field_name][$langcode][$property_name] = (!empty($column_attributes['serialize'])) ? unserialize($row[$column_name]) : $row[$column_name];
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultipleRevisionsFieldItems($revision_ids) {
    $revisions = [];

    // Sanitize IDs. Before feeding ID array into buildQuery, check whether
    // it is empty as this would load all entity revisions.
    $revision_ids = $this->cleanIds($revision_ids, 'revision');

    if (!empty($revision_ids)) {
      // Build and execute the query.
      $query_result = $this->buildQuery(NULL, $revision_ids)->execute();
      $records = $query_result->fetchAllAssoc($this->revisionKey);

      // Map the loaded records into entity objects and according fields.
      if ($records) {
        $revisions = $this->mapFromStorageRecords($records, TRUE);
      }
    }

    return $revisions;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
    $this->database->delete($this->revisionTable)
      ->condition($this->revisionKey, $revision->getRevisionId())
      ->execute();

    if ($this->revisionDataTable) {
      $this->database->delete($this->revisionDataTable)
        ->condition($this->revisionKey, $revision->getRevisionId())
        ->execute();
    }

    $this->deleteRevisionFromDedicatedTables($revision);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    if ($this->dataTable) {
      // @todo We should not be using a condition to specify whether conditions
      //   apply to the default language. See
      //   https://www.drupal.org/node/1866330.
      // Default to the original entity language if not explicitly specified
      // otherwise.
      if (!array_key_exists($this->defaultLangcodeKey, $values)) {
        $values[$this->defaultLangcodeKey] = 1;
      }
      // If the 'default_langcode' flag is explicitly not set, we do not care
      // whether the queried values are in the original entity language or not.
      elseif ($values[$this->defaultLangcodeKey] === NULL) {
        unset($values[$this->defaultLangcodeKey]);
      }
    }

    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * Builds the query to load the entity.
   *
   * This has full revision support. For entities requiring special queries,
   * the class can be extended, and the default query can be constructed by
   * calling parent::buildQuery(). This is usually necessary when the object
   * being loaded needs to be augmented with additional data from another
   * table, such as loading vocabulary machine name into terms, however it
   * can also support $conditions on different tables.
   *
   * @param array|null $ids
   *   An array of entity IDs, or NULL to load all entities.
   * @param array|bool $revision_ids
   *   The IDs of the revisions to load, or FALSE if this query is asking for
   *   the default revisions. Defaults to FALSE.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SelectQuery object for loading the entity.
   */
  protected function buildQuery($ids, $revision_ids = FALSE) {
    $query = $this->database->select($this->baseTable, 'base');

    $query->addTag($this->entityTypeId . '_load_multiple');

    if ($revision_ids) {
      $query->join($this->revisionTable, 'revision', "[revision].[{$this->idKey}] = [base].[{$this->idKey}] AND [revision].[{$this->revisionKey}] IN (:revisionIds[])", [':revisionIds[]' => $revision_ids]);
    }
    elseif ($this->revisionTable) {
      $query->join($this->revisionTable, 'revision', "[revision].[{$this->revisionKey}] = [base].[{$this->revisionKey}]");
    }

    // Add fields from the {entity} table.
    $table_mapping = $this->getTableMapping();
    $entity_fields = $table_mapping->getAllColumns($this->baseTable);

    if ($this->revisionTable) {
      // Add all fields from the {entity_revision} table.
      $entity_revision_fields = $table_mapping->getAllColumns($this->revisionTable);
      $entity_revision_fields = array_combine($entity_revision_fields, $entity_revision_fields);
      // The ID field is provided by entity, so remove it.
      unset($entity_revision_fields[$this->idKey]);

      // Remove all fields from the base table that are also fields by the same
      // name in the revision table.
      $entity_field_keys = array_flip($entity_fields);
      foreach ($entity_revision_fields as $name) {
        if (isset($entity_field_keys[$name])) {
          unset($entity_fields[$entity_field_keys[$name]]);
        }
      }
      $query->fields('revision', $entity_revision_fields);

      // Compare revision ID of the base and revision table, if equal then this
      // is the default revision.
      $query->addExpression('CASE [base].[' . $this->revisionKey . '] WHEN [revision].[' . $this->revisionKey . '] THEN 1 ELSE 0 END', 'isDefaultRevision');
    }

    $query->fields('base', $entity_fields);

    if ($ids) {
      $query->condition("base.{$this->idKey}", $ids, 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }

    try {
      $transaction = $this->database->startTransaction();
      parent::delete($entities);

      // Ignore replica server temporarily.
      \Drupal::service('database.replica_kill_switch')->trigger();
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      Error::logException(\Drupal::logger($this->entityTypeId), $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
    $ids = array_keys($entities);

    $this->database->delete($this->baseTable)
      ->condition($this->idKey, $ids, 'IN')
      ->execute();

    if ($this->revisionTable) {
      $this->database->delete($this->revisionTable)
        ->condition($this->idKey, $ids, 'IN')
        ->execute();
    }

    if ($this->dataTable) {
      $this->database->delete($this->dataTable)
        ->condition($this->idKey, $ids, 'IN')
        ->execute();
    }

    if ($this->revisionDataTable) {
      $this->database->delete($this->revisionDataTable)
        ->condition($this->idKey, $ids, 'IN')
        ->execute();
    }

    foreach ($entities as $entity) {
      $this->deleteFromDedicatedTables($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    try {
      $transaction = $this->database->startTransaction();
      $return = parent::save($entity);

      // Ignore replica server temporarily.
      \Drupal::service('database.replica_kill_switch')->trigger();
      return $return;
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      Error::logException(\Drupal::logger($this->entityTypeId), $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function restore(EntityInterface $entity) {
    try {
      $transaction = $this->database->startTransaction();
      // Insert the entity data in the base and data tables only for default
      // revisions.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if ($entity->isDefaultRevision()) {
        $record = $this->mapToStorageRecord($entity->getUntranslated(), $this->baseTable);
        $this->database
          ->insert($this->baseTable)
          ->fields((array) $record)
          ->execute();

        if ($this->dataTable) {
          $this->saveToSharedTables($entity);
        }
      }

      // Insert the entity data in the revision and revision data tables.
      if ($this->revisionTable) {
        $record = $this->mapToStorageRecord($entity->getUntranslated(), $this->revisionTable);
        $this->database
          ->insert($this->revisionTable)
          ->fields((array) $record)
          ->execute();

        if ($this->revisionDataTable) {
          $this->saveToSharedTables($entity, $this->revisionDataTable);
        }
      }

      // Insert the entity data in the dedicated tables.
      $this->saveToDedicatedTables($entity, FALSE, []);

      // Ignore replica server temporarily.
      \Drupal::service('database.replica_kill_switch')->trigger();
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      Error::logException(\Drupal::logger($this->entityTypeId), $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    $full_save = empty($names);
    $update = !$full_save || !$entity->isNew();

    if ($full_save) {
      $shared_table_fields = TRUE;
      $dedicated_table_fields = TRUE;
    }
    else {
      $table_mapping = $this->getTableMapping();
      $shared_table_fields = FALSE;
      $dedicated_table_fields = [];

      // Collect the name of fields to be written in dedicated tables and check
      // whether shared table records need to be updated.
      foreach ($names as $name) {
        $storage_definition = $this->fieldStorageDefinitions[$name];
        if ($table_mapping->allowsSharedTableStorage($storage_definition)) {
          $shared_table_fields = TRUE;
        }
        elseif ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
          $dedicated_table_fields[] = $name;
        }
      }
    }

    // Update shared table records if necessary.
    if ($shared_table_fields) {
      $record = $this->mapToStorageRecord($entity->getUntranslated(), $this->baseTable);
      // Create the storage record to be saved.
      if ($update) {
        $default_revision = $entity->isDefaultRevision();
        if ($default_revision) {
          $id = $record->{$this->idKey};
          // Remove the ID from the record to enable updates on SQL variants
          // that prevent updating serial columns, for example, mssql.
          unset($record->{$this->idKey});
          $this->database
            ->update($this->baseTable)
            ->fields((array) $record)
            ->condition($this->idKey, $id)
            ->execute();
        }
        if ($this->revisionTable) {
          if ($full_save) {
            $entity->{$this->revisionKey} = $this->saveRevision($entity);
          }
          else {
            $record = $this->mapToStorageRecord($entity->getUntranslated(), $this->revisionTable);
            // Remove the revision ID from the record to enable updates on SQL
            // variants that prevent updating serial columns, for example,
            // mssql.
            unset($record->{$this->revisionKey});
            $entity->preSaveRevision($this, $record);
            $this->database
              ->update($this->revisionTable)
              ->fields((array) $record)
              ->condition($this->revisionKey, $entity->getRevisionId())
              ->execute();
          }
        }
        if ($default_revision && $this->dataTable) {
          $this->saveToSharedTables($entity);
        }
        if ($this->revisionDataTable) {
          $new_revision = $full_save && $entity->isNewRevision();
          $this->saveToSharedTables($entity, $this->revisionDataTable, $new_revision);
        }
      }
      else {
        // @todo Remove the 'return' option in Drupal 11.
        // @see https://www.drupal.org/project/drupal/issues/3256524
        $insert_id = $this->database
          ->insert($this->baseTable, ['return' => Database::RETURN_INSERT_ID])
          ->fields((array) $record)
          ->execute();
        // Even if this is a new entity the ID key might have been set, in which
        // case we should not override the provided ID. An ID key that is not set
        // to any value is interpreted as NULL (or DEFAULT) and thus overridden.
        if (!isset($record->{$this->idKey})) {
          $record->{$this->idKey} = $insert_id;
        }
        $entity->{$this->idKey} = (string) $record->{$this->idKey};
        if ($this->revisionTable) {
          $record->{$this->revisionKey} = $this->saveRevision($entity);
        }
        if ($this->dataTable) {
          $this->saveToSharedTables($entity);
        }
        if ($this->revisionDataTable) {
          $this->saveToSharedTables($entity, $this->revisionDataTable);
        }
      }
    }

    // Update dedicated table records if necessary.
    if ($dedicated_table_fields) {
      $names = is_array($dedicated_table_fields) ? $dedicated_table_fields : [];
      $this->saveToDedicatedTables($entity, $update, $names);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * Saves fields that use the shared tables.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param string $table_name
   *   (optional) The table name to save to. Defaults to the data table.
   * @param bool $new_revision
   *   (optional) Whether we are dealing with a new revision. By default fetches
   *   the information from the entity object.
   */
  protected function saveToSharedTables(ContentEntityInterface $entity, $table_name = NULL, $new_revision = NULL) {
    if (!isset($table_name)) {
      $table_name = $this->dataTable;
    }
    if (!isset($new_revision)) {
      $new_revision = $entity->isNewRevision();
    }
    $revision = $table_name != $this->dataTable;

    if (!$revision || !$new_revision) {
      $key = $revision ? $this->revisionKey : $this->idKey;
      $value = $revision ? $entity->getRevisionId() : $entity->id();
      // Delete and insert to handle removed values.
      $this->database->delete($table_name)
        ->condition($key, $value)
        ->execute();
    }

    $query = $this->database->insert($table_name);

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      $record = $this->mapToDataStorageRecord($translation, $table_name);
      $values = (array) $record;
      $query
        ->fields(array_keys($values))
        ->values($values);
    }

    $query->execute();
  }

  /**
   * Maps from an entity object to the storage record.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param string $table_name
   *   (optional) The table name to map records to. Defaults to the base table.
   *
   * @return object
   *   The record to store.
   */
  protected function mapToStorageRecord(ContentEntityInterface $entity, $table_name = NULL) {
    if (!isset($table_name)) {
      $table_name = $this->baseTable;
    }

    $record = new \stdClass();
    $table_mapping = $this->getTableMapping();
    foreach ($table_mapping->getFieldNames($table_name) as $field_name) {

      if (empty($this->fieldStorageDefinitions[$field_name])) {
        throw new EntityStorageException("Table mapping contains invalid field $field_name.");
      }
      $definition = $this->fieldStorageDefinitions[$field_name];
      $columns = $table_mapping->getColumnNames($field_name);

      foreach ($columns as $column_name => $schema_name) {
        // If there is no main property and only a single column, get all
        // properties from the first field item and assume that they will be
        // stored serialized.
        // @todo Give field types more control over this behavior in
        //   https://www.drupal.org/node/2232427.
        if (!$definition->getMainPropertyName() && count($columns) == 1) {
          $value = ($item = $entity->$field_name->first()) ? $item->getValue() : [];
        }
        else {
          $value = $entity->$field_name->$column_name ?? NULL;
        }
        if (!empty($definition->getSchema()['columns'][$column_name]['serialize'])) {
          $value = serialize($value);
        }

        // Do not set serial fields if we do not have a value. This supports all
        // SQL database drivers.
        // @see https://www.drupal.org/node/2279395
        $value = SqlContentEntityStorageSchema::castValue($definition->getSchema()['columns'][$column_name], $value);
        $empty_serial = empty($value) && $this->isColumnSerial($table_name, $schema_name);
        // The user entity is a very special case where the ID field is a serial
        // but we need to insert a row with an ID of 0 to represent the
        // anonymous user.
        // @todo https://drupal.org/i/3222123 implement a generic fix for all
        //   entity types.
        $user_zero = $this->entityTypeId === 'user' && $value === 0;
        if (!$empty_serial || $user_zero) {
          $record->$schema_name = $value;
        }
      }
    }

    return $record;
  }

  /**
   * Checks whether a field column should be treated as serial.
   *
   * @param $table_name
   *   The name of the table the field column belongs to.
   * @param $schema_name
   *   The schema name of the field column.
   *
   * @return bool
   *   TRUE if the column is serial, FALSE otherwise.
   */
  protected function isColumnSerial($table_name, $schema_name) {
    $result = FALSE;

    switch ($table_name) {
      case $this->baseTable:
        $result = $schema_name == $this->idKey;
        break;

      case $this->revisionTable:
        $result = $schema_name == $this->revisionKey;
        break;
    }

    return $result;
  }

  /**
   * Maps from an entity object to the storage record of the field data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $table_name
   *   (optional) The table name to map records to. Defaults to the data table.
   *
   * @return object
   *   The record to store.
   */
  protected function mapToDataStorageRecord(EntityInterface $entity, $table_name = NULL) {
    if (!isset($table_name)) {
      $table_name = $this->dataTable;
    }
    $record = $this->mapToStorageRecord($entity, $table_name);
    return $record;
  }

  /**
   * Saves an entity revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return int
   *   The revision id.
   */
  protected function saveRevision(ContentEntityInterface $entity) {
    $record = $this->mapToStorageRecord($entity->getUntranslated(), $this->revisionTable);

    $entity->preSaveRevision($this, $record);

    if ($entity->isNewRevision()) {
      // @todo Remove the 'return' option in Drupal 11.
      // @see https://www.drupal.org/project/drupal/issues/3256524
      $insert_id = $this->database
        ->insert($this->revisionTable, ['return' => Database::RETURN_INSERT_ID])
        ->fields((array) $record)
        ->execute();
      // Even if this is a new revision, the revision ID key might have been
      // set in which case we should not override the provided revision ID.
      if (!isset($record->{$this->revisionKey})) {
        $record->{$this->revisionKey} = $insert_id;
      }
      if ($entity->isDefaultRevision()) {
        $this->database->update($this->baseTable)
          ->fields([$this->revisionKey => $record->{$this->revisionKey}])
          ->condition($this->idKey, $record->{$this->idKey})
          ->execute();
      }
      // Make sure to update the new revision key for the entity.
      $entity->{$this->revisionKey}->value = $record->{$this->revisionKey};
    }
    else {
      // Remove the revision ID from the record to enable updates on SQL
      // variants that prevent updating serial columns, for example,
      // mssql.
      unset($record->{$this->revisionKey});
      $this->database
        ->update($this->revisionTable)
        ->fields((array) $record)
        ->condition($this->revisionKey, $entity->getRevisionId())
        ->execute();
    }
    return $entity->getRevisionId();
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.sql';
  }

  /**
   * Loads values of fields stored in dedicated tables for a group of entities.
   *
   * @param array &$values
   *   An array of values keyed by entity ID.
   * @param bool $load_from_revision
   *   Flag to indicate whether revisions should be loaded or not.
   */
  protected function loadFromDedicatedTables(array &$values, $load_from_revision) {
    if (empty($values)) {
      return;
    }

    // Collect entities ids, bundles and languages.
    $bundles = [];
    $ids = [];
    $default_langcodes = [];
    foreach ($values as $key => $entity_values) {
      $bundles[$this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : $this->entityTypeId] = TRUE;
      $ids[] = !$load_from_revision ? $key : $entity_values[$this->revisionKey][LanguageInterface::LANGCODE_DEFAULT];
      if ($this->langcodeKey && isset($entity_values[$this->langcodeKey][LanguageInterface::LANGCODE_DEFAULT])) {
        $default_langcodes[$key] = $entity_values[$this->langcodeKey][LanguageInterface::LANGCODE_DEFAULT];
      }
    }

    // Collect impacted fields.
    $storage_definitions = [];
    $definitions = [];
    $table_mapping = $this->getTableMapping();
    foreach ($bundles as $bundle => $v) {
      $definitions[$bundle] = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $bundle);
      foreach ($definitions[$bundle] as $field_name => $field_definition) {
        $storage_definition = $field_definition->getFieldStorageDefinition();
        if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
          $storage_definitions[$field_name] = $storage_definition;
        }
      }
    }

    // Load field data.
    $langcodes = array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL));
    foreach ($storage_definitions as $field_name => $storage_definition) {
      $table = !$load_from_revision ? $table_mapping->getDedicatedDataTableName($storage_definition) : $table_mapping->getDedicatedRevisionTableName($storage_definition);

      // Ensure that only values having valid languages are retrieved. Since we
      // are loading values for multiple entities, we cannot limit the query to
      // the available translations.
      $results = $this->database->select($table, 't')
        ->fields('t')
        ->condition(!$load_from_revision ? 'entity_id' : 'revision_id', $ids, 'IN')
        ->condition('deleted', 0)
        ->condition('langcode', $langcodes, 'IN')
        ->orderBy('delta')
        ->execute();

      foreach ($results as $row) {
        $bundle = $row->bundle;

        $value_key = !$load_from_revision ? $row->entity_id : $row->revision_id;
        // Field values in default language are stored with
        // LanguageInterface::LANGCODE_DEFAULT as key.
        $langcode = LanguageInterface::LANGCODE_DEFAULT;
        if ($this->langcodeKey && isset($default_langcodes[$value_key]) && $row->langcode != $default_langcodes[$value_key]) {
          $langcode = $row->langcode;
        }

        if (!isset($values[$value_key][$field_name][$langcode])) {
          $values[$value_key][$field_name][$langcode] = [];
        }

        // Ensure that records for non-translatable fields having invalid
        // languages are skipped.
        if ($langcode == LanguageInterface::LANGCODE_DEFAULT || $definitions[$bundle][$field_name]->isTranslatable()) {
          if ($storage_definition->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || count($values[$value_key][$field_name][$langcode]) < $storage_definition->getCardinality()) {
            $item = [];
            // For each column declared by the field, populate the item from the
            // prefixed database column.
            foreach ($storage_definition->getColumns() as $column => $attributes) {
              $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
              // Unserialize the value if specified in the column schema.
              $item[$column] = (!empty($attributes['serialize'])) ? unserialize($row->$column_name) : $row->$column_name;
            }

            // Add the item to the field values for the entity.
            $values[$value_key][$field_name][$langcode][] = $item;
          }
        }
      }
    }
  }

  /**
   * Saves values of fields that use dedicated tables.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $update
   *   TRUE if the entity is being updated, FALSE if it is being inserted.
   * @param string[] $names
   *   (optional) The names of the fields to be stored. Defaults to all the
   *   available fields.
   */
  protected function saveToDedicatedTables(ContentEntityInterface $entity, $update = TRUE, $names = []) {
    $vid = $entity->getRevisionId();
    $id = $entity->id();
    $bundle = $entity->bundle();
    $entity_type = $entity->getEntityTypeId();
    $default_langcode = $entity->getUntranslated()->language()->getId();
    $translation_langcodes = array_keys($entity->getTranslationLanguages());
    $table_mapping = $this->getTableMapping();

    if (!isset($vid)) {
      $vid = $id;
    }

    $original = !empty($entity->original) ? $entity->original : NULL;

    // Use the loaded revision instead of default one to check for data change.
    if ($original && !$entity->isNewRevision() && !$entity->isDefaultRevision()) {
      $original = $this->loadRevision($entity->getLoadedRevisionId());
    }

    // Determine which fields should be actually stored.
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    if ($names) {
      $definitions = array_intersect_key($definitions, array_flip($names));
    }

    foreach ($definitions as $field_name => $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        continue;
      }

      // When updating an existing revision, keep the existing records if the
      // field values did not change.
      if (!$entity->isNewRevision() && $original && !$this->hasFieldValueChanged($field_definition, $entity, $original)) {
        continue;
      }

      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
      $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition);

      // Delete and insert, rather than update, in case a value was added.
      if ($update) {
        // Only overwrite the field's base table if saving the default revision
        // of an entity.
        if ($entity->isDefaultRevision()) {
          $this->database->delete($table_name)
            ->condition('entity_id', $id)
            ->execute();
        }
        if ($this->entityType->isRevisionable()) {
          $this->database->delete($revision_name)
            ->condition('entity_id', $id)
            ->condition('revision_id', $vid)
            ->execute();
        }
      }

      // Prepare the multi-insert query.
      $do_insert = FALSE;
      $columns = ['entity_id', 'revision_id', 'bundle', 'delta', 'langcode'];
      foreach ($storage_definition->getColumns() as $column => $attributes) {
        $columns[] = $table_mapping->getFieldColumnName($storage_definition, $column);
      }
      $query = $this->database->insert($table_name)->fields($columns);
      if ($this->entityType->isRevisionable()) {
        $revision_query = $this->database->insert($revision_name)->fields($columns);
      }

      $langcodes = $field_definition->isTranslatable() ? $translation_langcodes : [$default_langcode];
      foreach ($langcodes as $langcode) {
        $delta_count = 0;
        $items = $entity->getTranslation($langcode)->get($field_name);
        $items->filterEmptyItems();
        foreach ($items as $delta => $item) {
          // We now know we have something to insert.
          $do_insert = TRUE;
          $record = [
            'entity_id' => $id,
            'revision_id' => $vid,
            'bundle' => $bundle,
            'delta' => $delta,
            'langcode' => $langcode,
          ];
          foreach ($storage_definition->getColumns() as $column => $attributes) {
            $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
            // Serialize the value if specified in the column schema.
            $value = $item->$column;
            if (!empty($attributes['serialize'])) {
              $value = serialize($value);
            }
            $record[$column_name] = SqlContentEntityStorageSchema::castValue($attributes, $value);
          }
          $query->values($record);
          if ($this->entityType->isRevisionable()) {
            $revision_query->values($record);
          }

          if ($storage_definition->getCardinality() != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && ++$delta_count == $storage_definition->getCardinality()) {
            break;
          }
        }
      }

      // Execute the query if we have values to insert.
      if ($do_insert) {
        // Only overwrite the field's base table if saving the default revision
        // of an entity.
        if ($entity->isDefaultRevision()) {
          $query->execute();
        }
        if ($this->entityType->isRevisionable()) {
          $revision_query->execute();
        }
      }
    }
  }

  /**
   * Deletes values of fields in dedicated tables for all revisions.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  protected function deleteFromDedicatedTables(ContentEntityInterface $entity) {
    $table_mapping = $this->getTableMapping();
    foreach ($this->fieldStorageDefinitions as $storage_definition) {
      if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        continue;
      }
      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
      $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition);
      $this->database->delete($table_name)
        ->condition('entity_id', $entity->id())
        ->execute();
      if ($this->entityType->isRevisionable()) {
        $this->database->delete($revision_name)
          ->condition('entity_id', $entity->id())
          ->execute();
      }
    }
  }

  /**
   * Deletes values of fields in dedicated tables for all revisions.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity. It must have a revision ID.
   */
  protected function deleteRevisionFromDedicatedTables(ContentEntityInterface $entity) {
    $vid = $entity->getRevisionId();
    if (isset($vid)) {
      $table_mapping = $this->getTableMapping();
      foreach ($this->fieldStorageDefinitions as $storage_definition) {
        if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
          continue;
        }
        $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition);
        $this->database->delete($revision_name)
          ->condition('entity_id', $entity->id())
          ->condition('revision_id', $vid)
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return $this->getStorageSchema()->requiresEntityStorageSchemaChanges($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return $this->getStorageSchema()->requiresFieldStorageSchemaChanges($storage_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return $this->getStorageSchema()->requiresEntityDataMigration($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldDataMigration(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return $this->getStorageSchema()->requiresFieldDataMigration($storage_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $this->wrapSchemaException(function () use ($entity_type) {
      $this->getStorageSchema()->onEntityTypeCreate($entity_type);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // Ensure we have an updated entity type definition.
    $this->entityType = $entity_type;
    // The table layout may have changed depending on the new entity type
    // definition.
    $this->initTableLayout();
    // Let the schema handler adapt to possible table layout changes.
    $this->wrapSchemaException(function () use ($entity_type, $original) {
      $this->getStorageSchema()->onEntityTypeUpdate($entity_type, $original);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->wrapSchemaException(function () use ($entity_type) {
      $this->getStorageSchema()->onEntityTypeDelete($entity_type);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeCreate(EntityTypeInterface $entity_type, array $field_storage_definitions) {
    $this->wrapSchemaException(function () use ($entity_type, $field_storage_definitions) {
      $this->getStorageSchema()->onFieldableEntityTypeCreate($entity_type, $field_storage_definitions);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
    $this->wrapSchemaException(function () use ($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, &$sandbox) {
      $this->getStorageSchema()->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $this->wrapSchemaException(function () use ($storage_definition) {
      $this->getStorageSchema()->onFieldStorageDefinitionCreate($storage_definition);
      $this->fieldStorageDefinitions[$storage_definition->getName()] = $storage_definition;
      $this->tableMapping = NULL;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $this->wrapSchemaException(function () use ($storage_definition, $original) {
      $this->getStorageSchema()->onFieldStorageDefinitionUpdate($storage_definition, $original);
      $this->fieldStorageDefinitions[$storage_definition->getName()] = $storage_definition;
      $this->tableMapping = NULL;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    $table_mapping = $this->getTableMapping();
    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      // Mark all data associated with the field for deletion.
      $table = $table_mapping->getDedicatedDataTableName($storage_definition);
      $revision_table = $table_mapping->getDedicatedRevisionTableName($storage_definition);
      $this->database->update($table)
        ->fields(['deleted' => 1])
        ->execute();
      if ($this->entityType->isRevisionable()) {
        $this->database->update($revision_table)
          ->fields(['deleted' => 1])
          ->execute();
      }
    }

    // Update the field schema.
    $this->wrapSchemaException(function () use ($storage_definition) {
      $this->getStorageSchema()->onFieldStorageDefinitionDelete($storage_definition);
      unset($this->fieldStorageDefinitions[$storage_definition->getName()]);
      $this->tableMapping = NULL;
    });
  }

  /**
   * Wraps a database schema exception into an entity storage exception.
   *
   * @param callable $callback
   *   The callback to be executed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When a database schema exception is thrown.
   */
  protected function wrapSchemaException(callable $callback) {
    $message = 'Exception thrown while performing a schema update.';
    try {
      $callback();
    }
    catch (SchemaException $e) {
      $message .= ' ' . $e->getMessage();
      throw new EntityStorageException($message, 0, $e);
    }
    catch (DatabaseExceptionWrapper $e) {
      $message .= ' ' . $e->getMessage();
      throw new EntityStorageException($message, 0, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) {
    $table_mapping = $this->getTableMapping();
    $storage_definition = $field_definition->getFieldStorageDefinition();
    // Mark field data as deleted.
    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
      $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition);
      $this->database->update($table_name)
        ->fields(['deleted' => 1])
        ->condition('bundle', $field_definition->getTargetBundle())
        ->execute();
      if ($this->entityType->isRevisionable()) {
        $this->database->update($revision_name)
          ->fields(['deleted' => 1])
          ->condition('bundle', $field_definition->getTargetBundle())
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleCreate($bundle, $entity_type_id) {}

  /**
   * {@inheritdoc}
   */
  public function onBundleDelete($bundle, $entity_type_id) {}

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    // Check whether the whole field storage definition is gone, or just some
    // bundle fields.
    $storage_definition = $field_definition->getFieldStorageDefinition();
    $table_mapping = $this->getTableMapping();
    $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $storage_definition->isDeleted());

    // Get the entities which we want to purge first.
    $entity_query = $this->database->select($table_name, 't', ['fetch' => \PDO::FETCH_ASSOC]);
    $or = $entity_query->orConditionGroup();
    foreach ($storage_definition->getColumns() as $column_name => $data) {
      $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $column_name));
    }
    $entity_query
      ->distinct(TRUE)
      ->fields('t', ['entity_id'])
      ->condition('bundle', $field_definition->getTargetBundle())
      ->range(0, $batch_size);

    // Create a map of field data table column names to field column names.
    $column_map = [];
    foreach ($storage_definition->getColumns() as $column_name => $data) {
      $column_map[$table_mapping->getFieldColumnName($storage_definition, $column_name)] = $column_name;
    }

    $entities = [];
    $items_by_entity = [];
    foreach ($entity_query->execute() as $row) {
      $item_query = $this->database->select($table_name, 't', ['fetch' => \PDO::FETCH_ASSOC])
        ->fields('t')
        ->condition('entity_id', $row['entity_id'])
        ->condition('deleted', 1)
        ->orderBy('delta');

      foreach ($item_query->execute() as $item_row) {
        if (!isset($entities[$item_row['revision_id']])) {
          // Create entity with the right revision id and entity id combination.
          $item_row['entity_type'] = $this->entityTypeId;
          // @todo: Replace this by an entity object created via an entity
          // factory, see https://www.drupal.org/node/1867228.
          $entities[$item_row['revision_id']] = _field_create_entity_from_ids((object) $item_row);
        }
        $item = [];
        foreach ($column_map as $db_column => $field_column) {
          $item[$field_column] = $item_row[$db_column];
        }
        $items_by_entity[$item_row['revision_id']][] = $item;
      }
    }

    // Create field item objects and return.
    foreach ($items_by_entity as $revision_id => $values) {
      $entity_adapter = $entities[$revision_id]->getTypedData();
      $items_by_entity[$revision_id] = \Drupal::typedDataManager()->create($field_definition, $values, $field_definition->getName(), $entity_adapter);
    }
    return $items_by_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
    $storage_definition = $field_definition->getFieldStorageDefinition();
    $is_deleted = $storage_definition->isDeleted();
    $table_mapping = $this->getTableMapping();
    $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
    $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $is_deleted);
    $revision_id = $this->entityType->isRevisionable() ? $entity->getRevisionId() : $entity->id();
    $this->database->delete($table_name)
      ->condition('revision_id', $revision_id)
      ->condition('deleted', 1)
      ->execute();
    if ($this->entityType->isRevisionable()) {
      $this->database->delete($revision_name)
        ->condition('revision_id', $revision_id)
        ->condition('deleted', 1)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {
    $this->getStorageSchema()->finalizePurge($storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    // Ensure that the table mapping is instantiated with the passed-in field
    // storage definition.
    $storage_definitions = $this->fieldStorageDefinitions;
    $storage_definitions[$storage_definition->getName()] = $storage_definition;
    $table_mapping = $this->getTableMapping($storage_definitions);

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $is_deleted = $storage_definition->isDeleted();
      if ($this->entityType->isRevisionable()) {
        $table_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $is_deleted);
      }
      else {
        $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
      }
      $query = $this->database->select($table_name, 't');
      $or = $query->orConditionGroup();
      foreach ($storage_definition->getColumns() as $column_name => $data) {
        $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $column_name));
      }
      $query->condition($or);
      if (!$as_bool) {
        $query
          ->fields('t', ['entity_id'])
          ->distinct(TRUE);
      }
    }
    elseif ($table_mapping->allowsSharedTableStorage($storage_definition)) {
      // Ascertain the table this field is mapped too.
      $field_name = $storage_definition->getName();
      $table_name = $table_mapping->getFieldTableName($field_name);
      $query = $this->database->select($table_name, 't');
      $or = $query->orConditionGroup();
      foreach (array_keys($storage_definition->getColumns()) as $property_name) {
        $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $property_name));
      }
      $query->condition($or);
      if (!$as_bool) {
        $query
          ->fields('t', [$this->idKey])
          ->distinct(TRUE);
      }
    }

    // @todo Find a way to count field data also for fields having custom
    //   storage. See https://www.drupal.org/node/2337753.
    $count = 0;
    if (isset($query)) {
      // If we are performing the query just to check if the field has data
      // limit the number of rows.
      if ($as_bool) {
        $query
          ->range(0, 1)
          ->addExpression('1');
      }
      else {
        // Otherwise count the number of rows.
        $query = $query->countQuery();
      }
      $count = $query->execute()->fetchField();
    }
    return $as_bool ? (bool) $count : (int) $count;
  }

}
