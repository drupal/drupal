<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
  }

  /**
   * Gets the base field definitions for a content entity type.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of base field definitions for the entity type, keyed by field
   *   name.
   */
  public function getFieldStorageDefinitions() {
    return $this->entityManager->getBaseFieldDefinitions($this->entityTypeId);
  }

  /**
   * Constructs a SqlContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->database = $database;
    $this->languageManager = $language_manager;
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

    // @todo Remove table names from the entity type definition in
    //   https://www.drupal.org/node/2232465.
    $this->baseTable = $this->entityType->getBaseTable() ?: $this->entityTypeId;
    $revisionable = $this->entityType->isRevisionable();
    if ($revisionable) {
      $this->revisionKey = $this->entityType->getKey('revision') ?: 'revision_id';
      $this->revisionTable = $this->entityType->getRevisionTable() ?: $this->entityTypeId . '_revision';
    }
    $translatable = $this->entityType->isTranslatable();
    if ($translatable) {
      $this->dataTable = $this->entityType->getDataTable() ?: $this->entityTypeId . '_field_data';
      $this->langcodeKey = $this->entityType->getKey('langcode');
      $this->defaultLangcodeKey = $this->entityType->getKey('default_langcode');
    }
    if ($revisionable && $translatable) {
      $this->revisionDataTable = $this->entityType->getRevisionDataTable() ?: $this->entityTypeId . '_field_revision';
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
      $this->storageSchema = new $class($this->entityManager, $this->entityType, $this, $this->database);
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
   * {@inheritdoc}
   */
  public function getTableMapping(array $storage_definitions = NULL) {
    $table_mapping = $this->tableMapping;

    // If we are using our internal storage definitions, which is our main use
    // case, we can statically cache the computed table mapping. If a new set
    // of field storage definitions is passed, for instance when comparing old
    // and new storage schema, we compute the table mapping without caching.
    // @todo Clean-up this in https://www.drupal.org/node/2274017 so we can
    //   easily instantiate a new table mapping whenever needed.
    if (!isset($this->tableMapping) || $storage_definitions) {
      $definitions = $storage_definitions ?: $this->entityManager->getFieldStorageDefinitions($this->entityTypeId);
      $table_mapping = new DefaultTableMapping($this->entityType, $definitions);

      $shared_table_definitions = array_filter($definitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
        return $table_mapping->allowsSharedTableStorage($definition);
      });

      $key_fields = array_values(array_filter([$this->idKey, $this->revisionKey, $this->bundleKey, $this->uuidKey, $this->langcodeKey]));
      $all_fields = array_keys($shared_table_definitions);
      $revisionable_fields = array_keys(array_filter($shared_table_definitions, function (FieldStorageDefinitionInterface $definition) {
        return $definition->isRevisionable();
      }));
      // Make sure the key fields come first in the list of fields.
      $all_fields = array_merge($key_fields, array_diff($all_fields, $key_fields));

      // Nodes have all three of these fields, while custom blocks only have
      // log.
      // @todo Provide automatic definitions for revision metadata fields in
      //   https://www.drupal.org/node/2248983.
      $revision_metadata_fields = array_intersect([
        'revision_timestamp',
        'revision_uid',
        'revision_log',
      ], $all_fields);

      $revisionable = $this->entityType->isRevisionable();
      $translatable = $this->entityType->isTranslatable();
      if (!$revisionable && !$translatable) {
        // The base layout stores all the base field values in the base table.
        $table_mapping->setFieldNames($this->baseTable, $all_fields);
      }
      elseif ($revisionable && !$translatable) {
        // The revisionable layout stores all the base field values in the base
        // table, except for revision metadata fields. Revisionable fields
        // denormalized in the base table but also stored in the revision table
        // together with the entity ID and the revision ID as identifiers.
        $table_mapping->setFieldNames($this->baseTable, array_diff($all_fields, $revision_metadata_fields));
        $revision_key_fields = [$this->idKey, $this->revisionKey];
        $table_mapping->setFieldNames($this->revisionTable, array_merge($revision_key_fields, $revisionable_fields));
      }
      elseif (!$revisionable && $translatable) {
        // Multilingual layouts store key field values in the base table. The
        // other base field values are stored in the data table, no matter
        // whether they are translatable or not. The data table holds also a
        // denormalized copy of the bundle field value to allow for more
        // performant queries. This means that only the UUID is not stored on
        // the data table.
        $table_mapping
          ->setFieldNames($this->baseTable, $key_fields)
          ->setFieldNames($this->dataTable, array_values(array_diff($all_fields, [$this->uuidKey])));
      }
      elseif ($revisionable && $translatable) {
        // The revisionable multilingual layout stores key field values in the
        // base table, except for language, which is stored in the revision
        // table along with revision metadata. The revision data table holds
        // data field values for all the revisionable fields and the data table
        // holds the data field values for all non-revisionable fields. The data
        // field values of revisionable fields are denormalized in the data
        // table, as well.
        $table_mapping->setFieldNames($this->baseTable, array_values($key_fields));

        // Like in the multilingual, non-revisionable case the UUID is not
        // in the data table. Additionally, do not store revision metadata
        // fields in the data table.
        $data_fields = array_values(array_diff($all_fields, [$this->uuidKey], $revision_metadata_fields));
        $table_mapping->setFieldNames($this->dataTable, $data_fields);

        $revision_base_fields = array_merge([$this->idKey, $this->revisionKey, $this->langcodeKey], $revision_metadata_fields);
        $table_mapping->setFieldNames($this->revisionTable, $revision_base_fields);

        $revision_data_key_fields = [$this->idKey, $this->revisionKey, $this->langcodeKey];
        $revision_data_fields = array_diff($revisionable_fields, $revision_metadata_fields, [$this->langcodeKey]);
        $table_mapping->setFieldNames($this->revisionDataTable, array_merge($revision_data_key_fields, $revision_data_fields));
      }

      // Add dedicated tables.
      $dedicated_table_definitions = array_filter($definitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
        return $table_mapping->requiresDedicatedTableStorage($definition);
      });
      $extra_columns = [
        'bundle',
        'deleted',
        'entity_id',
        'revision_id',
        'langcode',
        'delta',
      ];
      foreach ($dedicated_table_definitions as $field_name => $definition) {
        $tables = [$table_mapping->getDedicatedDataTableName($definition)];
        if ($revisionable && $definition->isRevisionable()) {
          $tables[] = $table_mapping->getDedicatedRevisionTableName($definition);
        }
        foreach ($tables as $table_name) {
          $table_mapping->setFieldNames($table_name, [$field_name]);
          $table_mapping->setExtraColumns($table_name, $extra_columns);
        }
      }

      // Cache the computed table mapping only if we are using our internal
      // storage definitions.
      if (!$storage_definitions) {
        $this->tableMapping = $table_mapping;
      }
    }

    return $table_mapping;
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
   *   Associative array of query results, keyed on the entity ID.
   * @param bool $load_from_revision
   *   Flag to indicate whether revisions should be loaded or not.
   *
   * @return array
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = FALSE) {
    if (!$records) {
      return [];
    }

    $values = [];
    foreach ($records as $id => $record) {
      $values[$id] = [];
      // Skip the item delta and item value levels (if possible) but let the
      // field assign the value as suiting. This avoids unnecessary array
      // hierarchies and saves memory here.
      foreach ($record as $name => $value) {
        // Handle columns named [field_name]__[column_name] (e.g for field types
        // that store several properties).
        if ($field_name = strstr($name, '__', TRUE)) {
          $property_name = substr($name, strpos($name, '__') + 2);
          $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$property_name] = $value;
        }
        else {
          // Handle columns named directly after the field (e.g if the field
          // type only stores one property).
          $values[$id][$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
        }
      }
    }

    // Initialize translations array.
    $translations = array_fill_keys(array_keys($values), []);

    // Load values from shared and dedicated tables.
    $this->loadFromSharedTables($values, $translations);
    $this->loadFromDedicatedTables($values, $load_from_revision);

    $entities = [];
    foreach ($values as $id => $entity_values) {
      $bundle = $this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : FALSE;
      // Turn the record into an entity class.
      $entities[$id] = new $this->entityClass($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));
    }

    return $entities;
  }

  /**
   * Loads values for fields stored in the shared data tables.
   *
   * @param array &$values
   *   Associative array of entities values, keyed on the entity ID.
   * @param array &$translations
   *   List of translations, keyed on the entity ID.
   */
  protected function loadFromSharedTables(array &$values, array &$translations) {
    if ($this->dataTable) {
      // If a revision table is available, we need all the properties of the
      // latest revision. Otherwise we fall back to the data table.
      $table = $this->revisionDataTable ?: $this->dataTable;
      $alias = $this->revisionDataTable ? 'revision' : 'data';
      $query = $this->database->select($table, $alias, ['fetch' => \PDO::FETCH_ASSOC])
        ->fields($alias)
        ->condition($alias . '.' . $this->idKey, array_keys($values), 'IN')
        ->orderBy($alias . '.' . $this->idKey);

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
          $query->leftJoin($this->dataTable, 'data', "(revision.$this->idKey = data.$this->idKey and revision.$this->langcodeKey = data.$this->langcodeKey)");
          $column_names = [];
          // Some fields can have more then one columns in the data table so
          // column names are needed.
          foreach ($data_fields as $data_field) {
            // \Drupal\Core\Entity\Sql\TableMappingInterface:: getColumNames()
            // returns an array keyed by property names so remove the keys
            // before array_merge() to avoid losing data with fields having the
            // same columns i.e. value.
            $column_names = array_merge($column_names, array_values($table_mapping->getColumnNames($data_field)));
          }
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
        $id = $row[$this->idKey];

        // Field values in default language are stored with
        // LanguageInterface::LANGCODE_DEFAULT as key.
        $langcode = empty($row[$this->defaultLangcodeKey]) ? $row[$this->langcodeKey] : LanguageInterface::LANGCODE_DEFAULT;

        $translations[$id][$langcode] = TRUE;

        foreach ($all_fields as $field_name) {
          $columns = $table_mapping->getColumnNames($field_name);
          // Do not key single-column fields by property name.
          if (count($columns) == 1) {
            $values[$id][$field_name][$langcode] = $row[reset($columns)];
          }
          else {
            foreach ($columns as $property_name => $column_name) {
              $values[$id][$field_name][$langcode][$property_name] = $row[$column_name];
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
    $revision = NULL;

    // Build and execute the query.
    $query_result = $this->buildQuery([], $revision_id)->execute();
    $records = $query_result->fetchAllAssoc($this->idKey);

    if (!empty($records)) {
      // Convert the raw records to entity objects.
      $entities = $this->mapFromStorageRecords($records, TRUE);
      $revision = reset($entities) ?: NULL;
    }

    return $revision;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
    $this->database->delete($this->revisionTable)
      ->condition($this->revisionKey, $revision->getRevisionId())
      ->execute();
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
   * table, such as loading node type into comments or vocabulary machine name
   * into terms, however it can also support $conditions on different tables.
   * See Drupal\comment\CommentStorage::buildQuery() for an example.
   *
   * @param array|null $ids
   *   An array of entity IDs, or NULL to load all entities.
   * @param $revision_id
   *   The ID of the revision to load, or FALSE if this query is asking for the
   *   most current revision(s).
   *
   * @return \Drupal\Core\Database\Query\Select
   *   A SelectQuery object for loading the entity.
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = $this->database->select($this->entityType->getBaseTable(), 'base');

    $query->addTag($this->entityTypeId . '_load_multiple');

    if ($revision_id) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->idKey} = base.{$this->idKey} AND revision.{$this->revisionKey} = :revisionId", [':revisionId' => $revision_id]);
    }
    elseif ($this->revisionTable) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->revisionKey} = base.{$this->revisionKey}");
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
      $query->addExpression('CASE base.' . $this->revisionKey . ' WHEN revision.' . $this->revisionKey . ' THEN 1 ELSE 0 END', 'isDefaultRevision');
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

    $transaction = $this->database->startTransaction();
    try {
      parent::delete($entities);

      // Ignore replica server temporarily.
      db_ignore_replica();
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      watchdog_exception($this->entityTypeId, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
    $ids = array_keys($entities);

    $this->database->delete($this->entityType->getBaseTable())
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
    $transaction = $this->database->startTransaction();
    try {
      $return = parent::save($entity);

      // Ignore replica server temporarily.
      db_ignore_replica();
      return $return;
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      watchdog_exception($this->entityTypeId, $e);
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
      $storage_definitions = $this->entityManager->getFieldStorageDefinitions($this->entityTypeId);
      $shared_table_fields = FALSE;
      $dedicated_table_fields = [];

      // Collect the name of fields to be written in dedicated tables and check
      // whether shared table records need to be updated.
      foreach ($names as $name) {
        $storage_definition = $storage_definitions[$name];
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
          $this->database
            ->update($this->baseTable)
            ->fields((array) $record)
            ->condition($this->idKey, $record->{$this->idKey})
            ->execute();
        }
        if ($this->revisionTable) {
          if ($full_save) {
            $entity->{$this->revisionKey} = $this->saveRevision($entity);
          }
          else {
            $record = $this->mapToStorageRecord($entity->getUntranslated(), $this->revisionTable);
            $entity->preSaveRevision($this, $record);
            $this->database
              ->update($this->revisionTable)
              ->fields((array) $record)
              ->condition($this->revisionKey, $record->{$this->revisionKey})
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
   * @return \stdClass
   *   The record to store.
   */
  protected function mapToStorageRecord(ContentEntityInterface $entity, $table_name = NULL) {
    if (!isset($table_name)) {
      $table_name = $this->baseTable;
    }

    $record = new \stdClass();
    $table_mapping = $this->getTableMapping();
    foreach ($table_mapping->getFieldNames($table_name) as $field_name) {

      if (empty($this->getFieldStorageDefinitions()[$field_name])) {
        throw new EntityStorageException("Table mapping contains invalid field $field_name.");
      }
      $definition = $this->getFieldStorageDefinitions()[$field_name];
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
          $value = isset($entity->$field_name->$column_name) ? $entity->$field_name->$column_name : NULL;
        }
        if (!empty($definition->getSchema()['columns'][$column_name]['serialize'])) {
          $value = serialize($value);
        }

        // Do not set serial fields if we do not have a value. This supports all
        // SQL database drivers.
        // @see https://www.drupal.org/node/2279395
        $value = drupal_schema_get_field_value($definition->getSchema()['columns'][$column_name], $value);
        if (!(empty($value) && $this->isColumnSerial($table_name, $schema_name))) {
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
   *
   * @see \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema::processBaseTable()
   * @see \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema::processRevisionTable()
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
   * @return \stdClass
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
        $this->database->update($this->entityType->getBaseTable())
          ->fields([$this->revisionKey => $record->{$this->revisionKey}])
          ->condition($this->idKey, $record->{$this->idKey})
          ->execute();
      }
    }
    else {
      $this->database
        ->update($this->revisionTable)
        ->fields((array) $record)
        ->condition($this->revisionKey, $record->{$this->revisionKey})
        ->execute();
    }

    // Make sure to update the new revision key for the entity.
    $entity->{$this->revisionKey}->value = $record->{$this->revisionKey};

    return $record->{$this->revisionKey};
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
   *   (optional) Flag to indicate whether revisions should be loaded or not,
   *   defaults to FALSE.
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
      $definitions[$bundle] = $this->entityManager->getFieldDefinitions($this->entityTypeId, $bundle);
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

        // Field values in default language are stored with
        // LanguageInterface::LANGCODE_DEFAULT as key.
        $langcode = LanguageInterface::LANGCODE_DEFAULT;
        if ($this->langcodeKey && isset($default_langcodes[$row->entity_id]) && $row->langcode != $default_langcodes[$row->entity_id]) {
          $langcode = $row->langcode;
        }

        if (!isset($values[$row->entity_id][$field_name][$langcode])) {
          $values[$row->entity_id][$field_name][$langcode] = [];
        }

        // Ensure that records for non-translatable fields having invalid
        // languages are skipped.
        if ($langcode == LanguageInterface::LANGCODE_DEFAULT || $definitions[$bundle][$field_name]->isTranslatable()) {
          if ($storage_definition->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || count($values[$row->entity_id][$field_name][$langcode]) < $storage_definition->getCardinality()) {
            $item = [];
            // For each column declared by the field, populate the item from the
            // prefixed database column.
            foreach ($storage_definition->getColumns() as $column => $attributes) {
              $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
              // Unserialize the value if specified in the column schema.
              $item[$column] = (!empty($attributes['serialize'])) ? unserialize($row->$column_name) : $row->$column_name;
            }

            // Add the item to the field values for the entity.
            $values[$row->entity_id][$field_name][$langcode][] = $item;
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

    // Determine which fields should be actually stored.
    $definitions = $this->entityManager->getFieldDefinitions($entity_type, $bundle);
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
            $record[$column_name] = drupal_schema_get_field_value($attributes, $value);
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
    foreach ($this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();
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
      foreach ($this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $field_definition) {
        $storage_definition = $field_definition->getFieldStorageDefinition();
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
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    // If we are adding a field stored in a shared table we need to recompute
    // the table mapping.
    // @todo This does not belong here. Remove it once we are able to generate a
    //   fresh table mapping in the schema handler. See
    //   https://www.drupal.org/node/2274017.
    if ($this->getTableMapping()->allowsSharedTableStorage($storage_definition)) {
      $this->tableMapping = NULL;
    }
    $this->wrapSchemaException(function () use ($storage_definition) {
      $this->getStorageSchema()->onFieldStorageDefinitionCreate($storage_definition);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $this->wrapSchemaException(function () use ($storage_definition, $original) {
      $this->getStorageSchema()->onFieldStorageDefinitionUpdate($storage_definition, $original);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    $table_mapping = $this->getTableMapping(
      $this->entityManager->getLastInstalledFieldStorageDefinitions($this->entityType->id())
    );

    // @todo Remove the FieldStorageConfigInterface check when non-configurable
    //   fields support purging: https://www.drupal.org/node/2282119.
    if ($storage_definition instanceof FieldStorageConfigInterface && $table_mapping->requiresDedicatedTableStorage($storage_definition)) {
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
  public function onBundleCreate($bundle, $entity_type_id) { }

  /**
   * {@inheritdoc}
   */
  public function onBundleDelete($bundle, $entity_type_id) { }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    // Check whether the whole field storage definition is gone, or just some
    // bundle fields.
    $storage_definition = $field_definition->getFieldStorageDefinition();
    $is_deleted = $this->storageDefinitionIsDeleted($storage_definition);
    $table_mapping = $this->getTableMapping();
    $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);

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
    $is_deleted = $this->storageDefinitionIsDeleted($storage_definition);
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
    // The table mapping contains stale data during a request when a field
    // storage definition is added, so bypass the internal storage definitions
    // and fetch the table mapping using the passed in storage definition.
    // @todo Fix this in https://www.drupal.org/node/2705205.
    $storage_definitions = $this->entityManager->getFieldStorageDefinitions($this->entityTypeId);
    $storage_definitions[$storage_definition->getName()] = $storage_definition;
    $table_mapping = $this->getTableMapping($storage_definitions);

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $is_deleted = $this->storageDefinitionIsDeleted($storage_definition);
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
      try {
        $table_name = $table_mapping->getFieldTableName($field_name);
      }
      catch (SqlContentEntityStorageException $e) {
        // This may happen when changing field storage schema, since we are not
        // able to use a table mapping matching the passed storage definition.
        // @todo Revisit this once we are able to instantiate the table mapping
        //   properly. See https://www.drupal.org/node/2274017.
        $table_name = $this->dataTable ?: $this->baseTable;
      }
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

  /**
   * Determines whether the passed field has been already deleted.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   *
   * @return bool
   *   Whether the field has been already deleted.
   */
  protected function storageDefinitionIsDeleted(FieldStorageDefinitionInterface $storage_definition) {
    // Configurable fields are marked for deletion.
    if ($storage_definition instanceOf FieldStorageConfigInterface) {
      return $storage_definition->isDeleted();
    }
    // For non configurable fields check whether they are still in the last
    // installed schema repository.
    return !array_key_exists($storage_definition->getName(), $this->entityManager->getLastInstalledFieldStorageDefinitions($this->entityTypeId));
  }

}
