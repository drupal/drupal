<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\SqlContentEntityStorage.
 */

namespace Drupal\Core\Entity\Sql;

use Drupal\Component\Utility\String;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
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
class SqlContentEntityStorage extends ContentEntityStorageBase implements SqlEntityStorageInterface, DynamicallyFieldableEntityStorageSchemaInterface, EntityBundleListenerInterface  {

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
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity type's storage schema object.
   *
   * @var \Drupal\Core\Entity\Schema\EntityStorageSchemaInterface
   */
  protected $storageSchema;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity')
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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to be used.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache) {
    parent::__construct($entity_type);
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->cacheBackend = $cache;
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
    //   https://drupal.org/node/2232465
    $this->baseTable = $this->entityType->getBaseTable() ?: $this->entityTypeId;
    $revisionable = $this->entityType->isRevisionable();
    if ($revisionable) {
      $this->revisionKey = $this->entityType->getKey('revision') ?: 'revision_id';
      $this->revisionTable = $this->entityType->getRevisionTable() ?: $this->entityTypeId . '_revision';
    }
    $translatable = $this->entityType->isTranslatable();
    if ($translatable) {
      $this->dataTable = $this->entityType->getDataTable() ?: $this->entityTypeId . '_field_data';
      $this->langcodeKey = $this->entityType->getKey('langcode') ?: 'langcode';
      $this->defaultLangcodeKey = $this->entityType->getKey('default_langcode') ?: 'default_langcode';
    }
    if ($revisionable && $translatable) {
      $this->revisionDataTable = $this->entityType->getRevisionDataTable() ?: $this->entityTypeId . '_field_revision';
    }
  }

  /**
   * Returns the base table name.
   *
   * @return string
   *   The table name.
   */
  public function getBaseTable() {
    return $this->baseTable;
  }

  /**
   * Returns the revision table name.
   *
   * @return string|false
   *   The table name or FALSE if it is not available.
   */
  public function getRevisionTable() {
    return $this->revisionTable;
  }

  /**
   * Returns the data table name.
   *
   * @return string|false
   *   The table name or FALSE if it is not available.
   */
  public function getDataTable() {
    return $this->dataTable;
  }

  /**
   * Returns the revision data table name.
   *
   * @return string|false
   *   The table name or FALSE if it is not available.
   */
  public function getRevisionDataTable() {
    return $this->revisionDataTable;
  }

  /**
   * Returns the entity type's storage schema object.
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
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   See https://www.drupal.org/node/2274017.
   */
  public function setEntityType(EntityTypeInterface $entity_type) {
    if ($this->entityType->id() == $entity_type->id()) {
      $this->entityType = $entity_type;
      $this->initTableLayout();
    }
    else {
      throw new EntityStorageException(String::format('Unsupported entity type @id', array('@id' => $entity_type->id())));
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
      $table_mapping = new DefaultTableMapping($definitions);

      $definitions = array_filter($definitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
        return $table_mapping->allowsSharedTableStorage($definition);
      });

      $key_fields = array_values(array_filter(array($this->idKey, $this->revisionKey, $this->bundleKey, $this->uuidKey, $this->langcodeKey)));
      $all_fields = array_keys($definitions);
      $revisionable_fields = array_keys(array_filter($definitions, function (FieldStorageDefinitionInterface $definition) {
        return $definition->isRevisionable();
      }));
      // Make sure the key fields come first in the list of fields.
      $all_fields = array_merge($key_fields, array_diff($all_fields, $key_fields));

      // Nodes have all three of these fields, while custom blocks only have
      // log.
      // @todo Provide automatic definitions for revision metadata fields in
      //   https://drupal.org/node/2248983.
      $revision_metadata_fields = array_intersect(array(
        'revision_timestamp',
        'revision_uid',
        'revision_log',
      ), $all_fields);

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
        $revision_key_fields = array($this->idKey, $this->revisionKey);
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
          ->setFieldNames($this->dataTable, array_values(array_diff($all_fields, array($this->uuidKey))))
          // Add the denormalized 'default_langcode' field to the mapping. Its
          // value is identical to the query expression
          // "base_table.langcode = data_table.langcode"
          ->setExtraColumns($this->dataTable, array('default_langcode'));
      }
      elseif ($revisionable && $translatable) {
        // The revisionable multilingual layout stores key field values in the
        // base table, except for language, which is stored in the revision
        // table along with revision metadata. The revision data table holds
        // data field values for all the revisionable fields and the data table
        // holds the data field values for all non-revisionable fields. The data
        // field values of revisionable fields are denormalized in the data
        // table, as well.
        $table_mapping->setFieldNames($this->baseTable, array_values(array_diff($key_fields, array($this->langcodeKey))));

        // Like in the multilingual, non-revisionable case the UUID is not
        // in the data table. Additionally, do not store revision metadata
        // fields in the data table.
        $data_fields = array_values(array_diff($all_fields, array($this->uuidKey), $revision_metadata_fields));
        $table_mapping
          ->setFieldNames($this->dataTable, $data_fields)
          // Add the denormalized 'default_langcode' field to the mapping. Its
          // value is identical to the query expression
          // "base_langcode = data_table.langcode" where "base_langcode" is
          // the language code of the default revision.
          ->setExtraColumns($this->dataTable, array('default_langcode'));

        $revision_base_fields = array_merge(array($this->idKey, $this->revisionKey, $this->langcodeKey), $revision_metadata_fields);
        $table_mapping->setFieldNames($this->revisionTable, $revision_base_fields);

        $revision_data_key_fields = array($this->idKey, $this->revisionKey, $this->langcodeKey);
        $revision_data_fields = array_diff($revisionable_fields, $revision_metadata_fields, array($this->langcodeKey));
        $table_mapping
          ->setFieldNames($this->revisionDataTable, array_merge($revision_data_key_fields, $revision_data_fields))
          // Add the denormalized 'default_langcode' field to the mapping. Its
          // value is identical to the query expression
          // "revision_table.langcode = data_table.langcode".
          ->setExtraColumns($this->revisionDataTable, array('default_langcode'));
      }

      // Add dedicated tables.
      $definitions = array_filter($definitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
        return $table_mapping->requiresDedicatedTableStorage($definition);
      });
      $extra_columns = array(
        'bundle',
        'deleted',
        'entity_id',
        'revision_id',
        'langcode',
        'delta',
      );
      foreach ($definitions as $field_name => $definition) {
        foreach (array($table_mapping->getDedicatedDataTableName($definition), $table_mapping->getDedicatedRevisionTableName($definition)) as $table_name) {
          $table_mapping->setFieldNames($table_name, array($field_name));
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
    $entities_from_storage = $this->getFromStorage($ids);
    $this->setPersistentCache($entities_from_storage);

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
    $entities = array();

    if ($ids === NULL || $ids) {
      // Build and execute the query.
      $query_result = $this->buildQuery($ids)->execute();
      $records = $query_result->fetchAllAssoc($this->idKey);

      // Map the loaded records into entity objects and according fields.
      if ($records) {
        $entities = $this->mapFromStorageRecords($records);

        // Call hook_entity_storage_load().
        foreach ($this->moduleHandler()->getImplementations('entity_storage_load') as $module) {
          $function = $module . '_entity_storage_load';
          $function($entities, $this->entityTypeId);
        }
        // Call hook_TYPE_storage_load().
        foreach ($this->moduleHandler()->getImplementations($this->entityTypeId . '_storage_load') as $module) {
          $function = $module . '_' . $this->entityTypeId . '_storage_load';
          $function($entities);
        }
      }
    }

    return $entities;
  }

  /**
   * Gets entities from the persistent cache backend.
   *
   * @param array|null &$ids
   *   If not empty, return entities that match these IDs. IDs that were found
   *   will be removed from the list.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the persistent cache.
   */
  protected function getFromPersistentCache(array &$ids = NULL) {
    if (!$this->entityType->isPersistentlyCacheable() || empty($ids)) {
      return array();
    }
    $entities = array();
    // Build the list of cache entries to retrieve.
    $cid_map = array();
    foreach ($ids as $id) {
      $cid_map[$id] = $this->buildCacheId($id);
    }
    $cids = array_values($cid_map);
    if ($cache = $this->cacheBackend->getMultiple($cids)) {
      // Get the entities that were found in the cache.
      foreach ($ids as $index => $id) {
        $cid = $cid_map[$id];
        if (isset($cache[$cid])) {
          $entities[$id] = $cache[$cid]->data;
          unset($ids[$index]);
        }
      }
    }
    return $entities;
  }

  /**
   * Stores entities in the persistent cache backend.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Entities to store in the cache.
   */
  protected function setPersistentCache($entities) {
    if (!$this->entityType->isPersistentlyCacheable()) {
      return;
    }

    $cache_tags = array(
      $this->entityTypeId . '_values',
      'entity_field_info',
    );
    foreach ($entities as $id => $entity) {
      $this->cacheBackend->set($this->buildCacheId($id), $entity, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
  }

  /**
   * Invokes hook_entity_load_uncached().
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   List of entities, keyed on the entity ID.
   */
  protected function invokeLoadUncachedHook(array &$entities) {
    if (!empty($entities)) {
      // Call hook_entity_load_uncached().
      foreach ($this->moduleHandler()->getImplementations('entity_load_uncached') as $module) {
        $function = $module . '_entity_load_uncached';
        $function($entities, $this->entityTypeId);
      }
      // Call hook_TYPE_load_uncached().
      foreach ($this->moduleHandler()->getImplementations($this->entityTypeId . '_load_uncached') as $module) {
        $function = $module . '_' . $this->entityTypeId . '_load_uncached';
        $function($entities);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    if ($ids) {
      $cids = array();
      foreach ($ids as $id) {
        unset($this->entities[$id]);
        $cids[] = $this->buildCacheId($id);
      }
      if ($this->entityType->isPersistentlyCacheable()) {
        $this->cacheBackend->deleteMultiple($cids);
      }
    }
    else {
      $this->entities = array();
      if ($this->entityType->isPersistentlyCacheable()) {
        $this->cacheBackend->deleteTags(array($this->entityTypeId . '_values'));
      }
    }
  }

  /**
   * Returns the cache ID for the passed in entity ID.
   *
   * @param int $id
   *   Entity ID for which the cache ID should be built.
   *
   * @return string
   *   Cache ID that can be passed to the cache backend.
   */
  protected function buildCacheId($id) {
    return "values:{$this->entityTypeId}:$id";
  }

  /**
   * Maps from storage records to entity objects, and attaches fields.
   *
   * @param array $records
   *   Associative array of query results, keyed on the entity ID.
   *
   * @return array
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records) {
    if (!$records) {
      return array();
    }

    $entities = array();
    foreach ($records as $id => $record) {
      $entities[$id] = array();
      // Skip the item delta and item value levels (if possible) but let the
      // field assign the value as suiting. This avoids unnecessary array
      // hierarchies and saves memory here.
      foreach ($record as $name => $value) {
        // Handle columns named [field_name]__[column_name] (e.g for field types
        // that store several properties).
        if ($field_name = strstr($name, '__', TRUE)) {
          $property_name = substr($name, strpos($name, '__') + 2);
          $entities[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$property_name] = $value;
        }
        else {
          // Handle columns named directly after the field (e.g if the field
          // type only stores one property).
          $entities[$id][$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
        }
      }
      // If we have no multilingual values we can instantiate entity objects
      // right now, otherwise we need to collect all the field values first.
      if (!$this->dataTable) {
        $bundle = $this->bundleKey ? $record->{$this->bundleKey} : FALSE;
        // Turn the record into an entity class.
        $entities[$id] = new $this->entityClass($entities[$id], $this->entityTypeId, $bundle);
      }
    }
    $this->attachPropertyData($entities);

    // Attach field values.
    $this->loadFieldItems($entities);

    return $entities;
  }

  /**
   * Attaches property data in all languages for translatable properties.
   *
   * @param array &$entities
   *   Associative array of entities, keyed on the entity ID.
   */
  protected function attachPropertyData(array &$entities) {
    if ($this->dataTable) {
      // If a revision table is available, we need all the properties of the
      // latest revision. Otherwise we fall back to the data table.
      $table = $this->revisionDataTable ?: $this->dataTable;
      $query = $this->database->select($table, 'data', array('fetch' => \PDO::FETCH_ASSOC))
        ->fields('data')
        ->condition($this->idKey, array_keys($entities))
        ->orderBy('data.' . $this->idKey);

      if ($this->revisionDataTable) {
        // Get the revision IDs.
        $revision_ids = array();
        foreach ($entities as $values) {
          $revision_ids[] = is_object($values) ? $values->getRevisionId() : $values[$this->revisionKey][LanguageInterface::LANGCODE_DEFAULT];
        }
        $query->condition($this->revisionKey, $revision_ids);
      }

      $data = $query->execute();

      $table_mapping = $this->getTableMapping();
      $translations = array();
      if ($this->revisionDataTable) {
        $data_fields = array_diff($table_mapping->getFieldNames($this->revisionDataTable), $table_mapping->getFieldNames($this->baseTable));
      }
      else {
        $data_fields = $table_mapping->getFieldNames($this->dataTable);
      }

      foreach ($data as $values) {
        $id = $values[$this->idKey];

        // Field values in default language are stored with
        // LanguageInterface::LANGCODE_DEFAULT as key.
        $langcode = empty($values['default_langcode']) ? $values['langcode'] : LanguageInterface::LANGCODE_DEFAULT;
        $translations[$id][$langcode] = TRUE;


        foreach ($data_fields as $field_name) {
          $columns = $table_mapping->getColumnNames($field_name);
          // Do not key single-column fields by property name.
          if (count($columns) == 1) {
            $entities[$id][$field_name][$langcode] = $values[reset($columns)];
          }
          else {
            foreach ($columns as $property_name => $column_name) {
              $entities[$id][$field_name][$langcode][$property_name] = $values[$column_name];
            }
          }
        }
      }

      foreach ($entities as $id => $values) {
        $bundle = $this->bundleKey ? $values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : FALSE;
        // Turn the record into an entity class.
        $entities[$id] = new $this->entityClass($values, $this->entityTypeId, $bundle, array_keys($translations[$id]));
      }
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageInterface::loadRevision().
   */
  public function loadRevision($revision_id) {
    // Build and execute the query.
    $query_result = $this->buildQuery(array(), $revision_id)->execute();
    $records = $query_result->fetchAllAssoc($this->idKey);

    if (!empty($records)) {
      // Convert the raw records to entity objects.
      $entities = $this->mapFromStorageRecords($records);
      $this->postLoad($entities);
      return reset($entities);
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageInterface::deleteRevision().
   */
  public function deleteRevision($revision_id) {
    if ($revision = $this->loadRevision($revision_id)) {
      // Prevent deletion if this is the default revision.
      if ($revision->isDefaultRevision()) {
        throw new EntityStorageException('Default revision can not be deleted');
      }

      $this->database->delete($this->revisionTable)
        ->condition($this->revisionKey, $revision->getRevisionId())
        ->execute();
      $this->invokeFieldMethod('deleteRevision', $revision);
      $this->deleteFieldItemsRevision($revision);
      $this->invokeHook('revision_delete', $revision);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    if ($this->dataTable) {
      // @todo We should not be using a condition to specify whether conditions
      //   apply to the default language. See http://drupal.org/node/1866330.
      // Default to the original entity language if not explicitly specified
      // otherwise.
      if (!array_key_exists('default_langcode', $values)) {
        $values['default_langcode'] = 1;
      }
      // If the 'default_langcode' flag is explicitly not set, we do not care
      // whether the queried values are in the original entity language or not.
      elseif ($values['default_langcode'] === NULL) {
        unset($values['default_langcode']);
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
      $query->join($this->revisionTable, 'revision', "revision.{$this->idKey} = base.{$this->idKey} AND revision.{$this->revisionKey} = :revisionId", array(':revisionId' => $revision_id));
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
      $query->addExpression('base.' . $this->revisionKey . ' = revision.' . $this->revisionKey, 'isDefaultRevision');
    }

    $query->fields('base', $entity_fields);

    if ($ids) {
      $query->condition("base.{$this->idKey}", $ids, 'IN');
    }

    return $query;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageInterface::delete().
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
      $transaction->rollback();
      watchdog_exception($this->entityTypeId, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    $ids = array_keys($entities);

    $this->database->delete($this->entityType->getBaseTable())
      ->condition($this->idKey, $ids)
      ->execute();

    if ($this->revisionTable) {
      $this->database->delete($this->revisionTable)
        ->condition($this->idKey, $ids)
        ->execute();
    }

    if ($this->dataTable) {
      $this->database->delete($this->dataTable)
        ->condition($this->idKey, $ids)
        ->execute();
    }

    if ($this->revisionDataTable) {
      $this->database->delete($this->revisionDataTable)
        ->condition($this->idKey, $ids)
        ->execute();
    }

    foreach ($entities as $entity) {
      $this->invokeFieldMethod('delete', $entity);
      $this->deleteFieldItems($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $transaction = $this->database->startTransaction();
    try {
      // Sync the changes made in the fields array to the internal values array.
      $entity->updateOriginalValues();

      $return = parent::save($entity);

      // Ignore replica server temporarily.
      db_ignore_replica();
      return $return;
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityTypeId, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    // Create the storage record to be saved.
    $record = $this->mapToStorageRecord($entity);

    $is_new = $entity->isNew();
    if (!$is_new) {
      if ($entity->isDefaultRevision()) {
        $this->database
          ->update($this->baseTable)
          ->fields((array) $record)
          ->condition($this->idKey, $record->{$this->idKey})
          ->execute();
        $return = SAVED_UPDATED;
      }
      else {
        // @todo, should a different value be returned when saving an entity
        // with $isDefaultRevision = FALSE?
        $return = FALSE;
      }
      if ($this->revisionTable) {
        $entity->{$this->revisionKey}->value = $this->saveRevision($entity);
      }
      if ($this->dataTable) {
        $this->savePropertyData($entity);
      }
      if ($this->revisionDataTable) {
        $this->savePropertyData($entity, $this->revisionDataTable);
      }
      if ($this->revisionTable) {
        $entity->setNewRevision(FALSE);
      }
    }
    else {
      // Ensure the entity is still seen as new after assigning it an id,
      // while storing its data.
      $entity->enforceIsNew();
      $insert_id = $this->database
        ->insert($this->baseTable, array('return' => Database::RETURN_INSERT_ID))
        ->fields((array) $record)
        ->execute();
      // Even if this is a new entity the ID key might have been set, in which
      // case we should not override the provided ID. An ID key that is not set
      // to any value is interpreted as NULL (or DEFAULT) and thus overridden.
      if (!isset($record->{$this->idKey})) {
        $record->{$this->idKey} = $insert_id;
      }
      $return = SAVED_NEW;
      $entity->{$this->idKey}->value = (string) $record->{$this->idKey};
      if ($this->revisionTable) {
        $entity->setNewRevision();
        $record->{$this->revisionKey} = $this->saveRevision($entity);
      }
      if ($this->dataTable) {
        $this->savePropertyData($entity);
      }
      if ($this->revisionDataTable) {
        $this->savePropertyData($entity, $this->revisionDataTable);
      }

      $entity->enforceIsNew(FALSE);
      if ($this->revisionTable) {
        $entity->setNewRevision(FALSE);
      }
    }
    $this->invokeFieldMethod($is_new ? 'insert' : 'update', $entity);
    $this->saveFieldItems($entity, !$is_new);

    if (!$is_new && $this->dataTable) {
      $this->invokeTranslationHooks($entity);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * Stores the entity property language-aware data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $table_name
   *   (optional) The table name to save to. Defaults to the data table.
   */
  protected function savePropertyData(EntityInterface $entity, $table_name = NULL) {
    if (!isset($table_name)) {
      $table_name = $this->dataTable;
    }
    $revision = $table_name != $this->dataTable;

    if (!$revision || !$entity->isNewRevision()) {
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
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    if ($hook == 'presave') {
      $this->invokeFieldMethod('preSave', $entity);
    }
    parent::invokeHook($hook, $entity);
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
        throw new EntityStorageException(String::format('Table mapping contains invalid field %field.', array('%field' => $field_name)));
      }
      $definition = $this->getFieldStorageDefinitions()[$field_name];
      $columns = $table_mapping->getColumnNames($field_name);

      foreach ($columns as $column_name => $schema_name) {
        // If there is no main property and only a single column, get all
        // properties from the first field item and assume that they will be
        // stored serialized.
        // @todo Give field types more control over this behavior in
        //   https://drupal.org/node/2232427.
        if (!$definition->getMainPropertyName() && count($columns) == 1) {
          $value = $entity->$field_name->first()->getValue();
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
   *   TRUE if the the column is serial, FALSE otherwise.
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
    $record->langcode = $entity->language()->getId();
    $record->default_langcode = intval($record->langcode == $entity->getUntranslated()->language()->getId());
    return $record;
  }

  /**
   * Saves an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return int
   *   The revision id.
   */
  protected function saveRevision(EntityInterface $entity) {
    $record = $this->mapToStorageRecord($entity, $this->revisionTable);

    $entity->preSaveRevision($this, $record);

    if ($entity->isNewRevision()) {
      $insert_id = $this->database
        ->insert($this->revisionTable, array('return' => Database::RETURN_INSERT_ID))
        ->fields((array) $record)
        ->execute();
      // Even if this is a new revision, the revision ID key might have been
      // set in which case we should not override the provided revision ID.
      if (!isset($record->{$this->revisionKey})) {
        $record->{$this->revisionKey} = $insert_id;
      }
      if ($entity->isDefaultRevision()) {
        $this->database->update($this->entityType->getBaseTable())
          ->fields(array($this->revisionKey => $record->{$this->revisionKey}))
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
  public function getQueryServiceName() {
    return 'entity.query.sql';
  }

  /**
   * Loads values of configurable fields for a group of entities.
   *
   * Loads all fields for each entity object in a group of a single entity type.
   * The loaded field values are added directly to the entity objects.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   An array of entities keyed by entity ID.
   */
  protected function loadFieldItems(array $entities) {
    if (empty($entities)) {
      return;
    }

    $age = static::FIELD_LOAD_CURRENT;
    foreach ($entities as $entity) {
      if (!$entity->isDefaultRevision()) {
        $age = static::FIELD_LOAD_REVISION;
        break;
      }
    }
    $load_current = $age == static::FIELD_LOAD_CURRENT;

    // Collect entities ids, bundles and languages.
    $bundles = array();
    $ids = array();
    $default_langcodes = array();
    foreach ($entities as $key => $entity) {
      $bundles[$entity->bundle()] = TRUE;
      $ids[] = $load_current ? $key : $entity->getRevisionId();
      $default_langcodes[$key] = $entity->getUntranslated()->language()->getId();
    }

    // Collect impacted fields.
    $storage_definitions = array();
    $definitions = array();
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
    $langcodes = array_keys(language_list(LanguageInterface::STATE_ALL));
    foreach ($storage_definitions as $field_name => $storage_definition) {
      $table = $load_current ? $table_mapping->getDedicatedDataTableName($storage_definition) : $table_mapping->getDedicatedRevisionTableName($storage_definition);

      // Ensure that only values having valid languages are retrieved. Since we
      // are loading values for multiple entities, we cannot limit the query to
      // the available translations.
      $results = $this->database->select($table, 't')
        ->fields('t')
        ->condition($load_current ? 'entity_id' : 'revision_id', $ids, 'IN')
        ->condition('deleted', 0)
        ->condition('langcode', $langcodes, 'IN')
        ->orderBy('delta')
        ->execute();

      $delta_count = array();
      foreach ($results as $row) {
        $bundle = $entities[$row->entity_id]->bundle();

        // Ensure that records for non-translatable fields having invalid
        // languages are skipped.
        if ($row->langcode == $default_langcodes[$row->entity_id] || $definitions[$bundle][$field_name]->isTranslatable()) {
          if (!isset($delta_count[$row->entity_id][$row->langcode])) {
            $delta_count[$row->entity_id][$row->langcode] = 0;
          }

          if ($storage_definition->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $delta_count[$row->entity_id][$row->langcode] < $storage_definition->getCardinality()) {
            $item = array();
            // For each column declared by the field, populate the item from the
            // prefixed database column.
            foreach ($storage_definition->getColumns() as $column => $attributes) {
              $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
              // Unserialize the value if specified in the column schema.
              $item[$column] = (!empty($attributes['serialize'])) ? unserialize($row->$column_name) : $row->$column_name;
            }

            // Add the item to the field values for the entity.
            $entities[$row->entity_id]->getTranslation($row->langcode)->{$field_name}[$delta_count[$row->entity_id][$row->langcode]] = $item;
            $delta_count[$row->entity_id][$row->langcode]++;
          }
        }
      }
    }
  }

  /**
   * Saves values of configurable fields for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param bool $update
   *   TRUE if the entity is being updated, FALSE if it is being inserted.
   */
  protected function saveFieldItems(EntityInterface $entity, $update = TRUE) {
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

    foreach ($this->entityManager->getFieldDefinitions($entity_type, $bundle) as $field_name => $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
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
      $columns = array('entity_id', 'revision_id', 'bundle', 'delta', 'langcode');
      foreach ($storage_definition->getColumns() as $column => $attributes) {
        $columns[] = $table_mapping->getFieldColumnName($storage_definition, $column);
      }
      $query = $this->database->insert($table_name)->fields($columns);
      if ($this->entityType->isRevisionable()) {
        $revision_query = $this->database->insert($revision_name)->fields($columns);
      }

      $langcodes = $field_definition->isTranslatable() ? $translation_langcodes : array($default_langcode);
      foreach ($langcodes as $langcode) {
        $delta_count = 0;
        $items = $entity->getTranslation($langcode)->get($field_name);
        $items->filterEmptyItems();
        foreach ($items as $delta => $item) {
          // We now know we have something to insert.
          $do_insert = TRUE;
          $record = array(
            'entity_id' => $id,
            'revision_id' => $vid,
            'bundle' => $bundle,
            'delta' => $delta,
            'langcode' => $langcode,
          );
          foreach ($storage_definition->getColumns() as $column => $attributes) {
            $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
            // Serialize the value if specified in the column schema.
            $record[$column_name] = !empty($attributes['serialize']) ? serialize($item->$column) : $item->$column;
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
   * Deletes values of configurable fields for all revisions of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function deleteFieldItems(EntityInterface $entity) {
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
   * Deletes values of configurable fields for a single revision of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity. It must have a revision ID.
   */
  protected function deleteFieldItemsRevision(EntityInterface $entity) {
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
    $this->getStorageSchema()->onEntityTypeCreate($entity_type);
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
    $this->getStorageSchema()->onEntityTypeUpdate($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->getStorageSchema()->onEntityTypeDelete($entity_type);
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
    $this->getStorageSchema()->onFieldStorageDefinitionCreate($storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $this->getStorageSchema()->onFieldStorageDefinitionUpdate($storage_definition, $original);
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
        ->fields(array('deleted' => 1))
        ->execute();
      if ($this->entityType->isRevisionable()) {
        $this->database->update($revision_table)
          ->fields(array('deleted' => 1))
          ->execute();
      }
    }

    // Update the field schema.
    $this->getStorageSchema()->onFieldStorageDefinitionDelete($storage_definition);
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
        ->fields(array('deleted' => 1))
        ->condition('bundle', $field_definition->getTargetBundle())
        ->execute();
      if ($this->entityType->isRevisionable()) {
        $this->database->update($revision_name)
          ->fields(array('deleted' => 1))
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
  public function onBundleRename($bundle, $bundle_new, $entity_type_id) {
    // The method runs before the field definitions are updated, so we use the
    // old bundle name.
    $field_definitions = $this->entityManager->getFieldDefinitions($this->entityTypeId, $bundle);
    // We need to handle deleted fields too. For now, this only makes sense for
    // configurable fields, so we use the specific API.
    // @todo Use the unified store of deleted field definitions instead in
    //   https://www.drupal.org/node/2282119
    $field_definitions += entity_load_multiple_by_properties('field_config', array('entity_type' => $this->entityTypeId, 'bundle' => $bundle, 'deleted' => TRUE, 'include_deleted' => TRUE));
    $table_mapping = $this->getTableMapping();

    foreach ($field_definitions as $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        $is_deleted = $this->storageDefinitionIsDeleted($storage_definition);
        $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
        $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $is_deleted);
        $this->database->update($table_name)
          ->fields(array('bundle' => $bundle_new))
          ->condition('bundle', $bundle)
          ->execute();
        if ($this->entityType->isRevisionable()) {
          $this->database->update($revision_name)
            ->fields(array('bundle' => $bundle_new))
            ->condition('bundle', $bundle)
            ->execute();
        }
      }
    }
  }

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
    $entity_query = $this->database->select($table_name, 't', array('fetch' => \PDO::FETCH_ASSOC));
    $or = $entity_query->orConditionGroup();
    foreach ($storage_definition->getColumns() as $column_name => $data) {
      $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $column_name));
    }
    $entity_query
      ->distinct(TRUE)
      ->fields('t', array('entity_id'))
      ->condition('bundle', $field_definition->getTargetBundle())
      ->range(0, $batch_size);

    // Create a map of field data table column names to field column names.
    $column_map = array();
    foreach ($storage_definition->getColumns() as $column_name => $data) {
      $column_map[$table_mapping->getFieldColumnName($storage_definition, $column_name)] = $column_name;
    }

    $entities = array();
    $items_by_entity = array();
    foreach ($entity_query->execute() as $row) {
      $item_query = $this->database->select($table_name, 't', array('fetch' => \PDO::FETCH_ASSOC))
        ->fields('t')
        ->condition('entity_id', $row['entity_id'])
        ->orderBy('delta');

      foreach ($item_query->execute() as $item_row) {
        if (!isset($entities[$item_row['revision_id']])) {
          // Create entity with the right revision id and entity id combination.
          $item_row['entity_type'] = $this->entityTypeId;
          // @todo: Replace this by an entity object created via an entity
          // factory, see https://drupal.org/node/1867228.
          $entities[$item_row['revision_id']] = _field_create_entity_from_ids((object) $item_row);
        }
        $item = array();
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
      ->execute();
    if ($this->entityType->isRevisionable()) {
      $this->database->delete($revision_name)
        ->condition('revision_id', $revision_id)
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
    $table_mapping = $this->getTableMapping();

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $is_deleted = $this->storageDefinitionIsDeleted($storage_definition);
      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
      $query = $this->database->select($table_name, 't');
      $or = $query->orConditionGroup();
      foreach ($storage_definition->getColumns() as $column_name => $data) {
        $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $column_name));
      }
      $query
        ->condition($or)
        ->fields('t', array('entity_id'))
        ->distinct(TRUE);
    }
    elseif ($table_mapping->allowsSharedTableStorage($storage_definition)) {
      $data_table = $this->dataTable ?: $this->baseTable;
      $query = $this->database->select($data_table, 't');
      $columns = $storage_definition->getColumns();
      if (count($columns) > 1) {
        $or = $query->orConditionGroup();
        foreach ($columns as $column_name => $data) {
          $or->isNotNull($storage_definition->getName() . '__' . $column_name);
        }
        $query->condition($or);
      }
      else {
        $query->isNotNull($storage_definition->getName());
      }
      $query
        ->fields('t', array($this->idKey))
        ->distinct(TRUE);
    }

    // @todo Find a way to count field data also for fields having custom
    //   storage. See https://www.drupal.org/node/2337753.
    $count = 0;
    if (isset($query)) {
      // If we are performing the query just to check if the field has data
      // limit the number of rows.
      if ($as_bool) {
        $query->range(0, 1);
      }
      $count = $query->countQuery()->execute()->fetchField();
    }
    return $as_bool ? (bool) $count : (int) $count;
  }

  /**
   * Returns whether the passed field has been already deleted.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   *
   * @return bool
   *   Whether the field has been already deleted.
   */
  protected function storageDefinitionIsDeleted(FieldStorageDefinitionInterface $storage_definition) {
    return !array_key_exists($storage_definition->getName(), $this->entityManager->getLastInstalledFieldStorageDefinitions($this->entityTypeId));
  }

}
