<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityDatabaseStorage.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Schema\ContentEntitySchemaHandler;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\FieldConfigUpdateForbiddenException;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldInstanceConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base entity controller class.
 *
 * Default implementation of Drupal\Core\Entity\EntityStorageInterface.
 *
 * This class can be used as-is by most simple entity types. Entity types
 * requiring special handling can extend the class.
 *
 * The class uses \Drupal\Core\Entity\Schema\ContentEntitySchemaHandler
 * internally in order to automatically generate the database schema based on
 * the defined base fields. Entity types can override
 * ContentEntityDatabaseStorage::getSchema() to customize the generated
 * schema; e.g., to add additional indexes.
 */
class ContentEntityDatabaseStorage extends ContentEntityStorageBase implements SqlEntityStorageInterface {

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
   * The entity schema handler.
   *
   * @var \Drupal\Core\Entity\Schema\EntitySchemaHandlerInterface
   */
  protected $schemaHandler;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager')
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
   * Constructs a ContentEntityDatabaseStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager) {
    parent::__construct($entity_type);

    $this->database = $database;
    $this->entityManager = $entity_manager;

    // @todo Remove table names from the entity type definition in
    //   https://drupal.org/node/2232465
    $this->baseTable = $this->entityType->getBaseTable() ?: $this->entityTypeId;

    $revisionable = $this->entityType->isRevisionable();
    if ($revisionable) {
      $this->revisionKey = $this->entityType->getKey('revision') ?: 'revision_id';
      $this->revisionTable = $this->entityType->getRevisionTable() ?: $this->entityTypeId . '_revision';
    }
    // @todo Remove the data table check once all entity types are using
    // entity query and we have a views data controller. See:
    // - https://drupal.org/node/2068325
    // - https://drupal.org/node/1740492
    $translatable = $this->entityType->isTranslatable() && $this->entityType->getDataTable();
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
   * {@inheritdoc}
   */
  public function getSchema() {
    return $this->schemaHandler()->getSchema();
  }

  /**
   * Gets the schema handler for this storage controller.
   *
   * @return \Drupal\Core\Entity\Schema\ContentEntitySchemaHandler
   *   The schema handler.
   */
  protected function schemaHandler() {
    if (!isset($this->schemaHandler)) {
      $this->schemaHandler = new ContentEntitySchemaHandler($this->entityManager, $this->entityType, $this);
    }
    return $this->schemaHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableMapping() {
    if (!isset($this->tableMapping)) {

      $definitions = array_filter($this->getFieldStorageDefinitions(), function (FieldDefinitionInterface $definition) {
        // @todo Remove the check for FieldDefinitionInterface::isMultiple() when
        //   multiple-value base fields are supported in
        //   https://drupal.org/node/2248977.
        return !$definition->isComputed() && !$definition->hasCustomStorage() && !$definition->isMultiple();
      });
      $this->tableMapping = new DefaultTableMapping($definitions);

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
      // @todo Remove the data table check once all entity types are using
      // entity query and we have a views data controller. See:
      // - https://drupal.org/node/2068325
      // - https://drupal.org/node/1740492
      $translatable = $this->entityType->getDataTable() && $this->entityType->isTranslatable();
      if (!$revisionable && !$translatable) {
        // The base layout stores all the base field values in the base table.
        $this->tableMapping->setFieldNames($this->baseTable, $all_fields);
      }
      elseif ($revisionable && !$translatable) {
        // The revisionable layout stores all the base field values in the base
        // table, except for revision metadata fields. Revisionable fields
        // denormalized in the base table but also stored in the revision table
        // together with the entity ID and the revision ID as identifiers.
        $this->tableMapping->setFieldNames($this->baseTable, array_diff($all_fields, $revision_metadata_fields));
        $revision_key_fields = array($this->idKey, $this->revisionKey);
        $this->tableMapping->setFieldNames($this->revisionTable, array_merge($revision_key_fields, $revisionable_fields));
      }
      elseif (!$revisionable && $translatable) {
        // Multilingual layouts store key field values in the base table. The
        // other base field values are stored in the data table, no matter
        // whether they are translatable or not. The data table holds also a
        // denormalized copy of the bundle field value to allow for more
        // performant queries. This means that only the UUID is not stored on
        // the data table.
        $this->tableMapping
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
        $this->tableMapping->setFieldNames($this->baseTable, array_values(array_diff($key_fields, array($this->langcodeKey))));

        // Like in the multilingual, non-revisionable case the UUID is not
        // in the data table. Additionally, do not store revision metadata
        // fields in the data table.
        $data_fields = array_values(array_diff($all_fields, array($this->uuidKey), $revision_metadata_fields));
        $this->tableMapping
          ->setFieldNames($this->dataTable, $data_fields)
          // Add the denormalized 'default_langcode' field to the mapping. Its
          // value is identical to the query expression
          // "base_langcode = data_table.langcode" where "base_langcode" is
          // the language code of the default revision.
          ->setExtraColumns($this->dataTable, array('default_langcode'));

        $revision_base_fields = array_merge(array($this->idKey, $this->revisionKey, $this->langcodeKey), $revision_metadata_fields);
        $this->tableMapping->setFieldNames($this->revisionTable, $revision_base_fields);

        $revision_data_key_fields = array($this->idKey, $this->revisionKey, $this->langcodeKey);
        $revision_data_fields = array_diff($revisionable_fields, $revision_metadata_fields);
        $this->tableMapping
          ->setFieldNames($this->revisionDataTable, array_merge($revision_data_key_fields, $revision_data_fields))
          // Add the denormalized 'default_langcode' field to the mapping. Its
          // value is identical to the query expression
          // "revision_table.langcode = data_table.langcode".
          ->setExtraColumns($this->revisionDataTable, array('default_langcode'));
      }
    }

    return $this->tableMapping;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    // Build and execute the query.
    $records = $this
      ->buildQuery($ids)
      ->execute()
      ->fetchAllAssoc($this->idKey);

    return $this->mapFromStorageRecords($records);
  }

  /**
   * Maps from storage records to entity objects.
   *
   * This will attach fields, if the entity is fieldable. It calls
   * hook_entity_load() for modules which need to add data to all entities.
   * It also calls hook_TYPE_load() on the loaded entities. For example
   * hook_node_load() or hook_user_load(). If your hook_TYPE_load()
   * expects special parameters apart from the queried entities, you can set
   * $this->hookLoadArguments prior to calling the method.
   * See Drupal\node\NodeStorage::attachLoad() for an example.
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
      // If we have no multilingual values we can instantiate entity objecs
      // right now, otherwise we need to collect all the field values first.
      if (!$this->dataTable) {
        $bundle = $this->bundleKey ? $record->{$this->bundleKey} : FALSE;
        // Turn the record into an entity class.
        $entities[$id] = new $this->entityClass($entities[$id], $this->entityTypeId, $bundle);
      }
    }
    $this->attachPropertyData($entities);

    // Attach field values.
    if ($this->entityType->isFieldable()) {
      $this->loadFieldItems($entities);
    }

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
        $data_fields = array_diff_key($table_mapping->getFieldNames($this->revisionDataTable), $table_mapping->getFieldNames($this->baseTable));
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

    // Reset the cache as soon as the changes have been applied.
    $this->resetCache($ids);
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
      $cache_ids = array($entity->id());
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
      // case we should not override the provided ID. An empty value for the
      // ID is interpreted as NULL and thus overridden.
      if (empty($record->{$this->idKey})) {
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
      // Reset general caches, but keep caches specific to certain entities.
      $cache_ids = array();
    }
    $this->invokeFieldMethod($is_new ? 'insert' : 'update', $entity);
    $this->saveFieldItems($entity, !$is_new);
    $this->resetCache($cache_ids);

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
        $record->$schema_name = drupal_schema_get_field_value($definition->getSchema()['columns'][$column_name], $value);
      }
    }

    return $record;
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
    $record->langcode = $entity->language()->id;
    $record->default_langcode = intval($record->langcode == $entity->getUntranslated()->language()->id);
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
      // Even if this is a new revsision, the revision ID key might have been
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
   * {@inheritdoc}
   */
  protected function doLoadFieldItems($entities, $age) {
    $load_current = $age == static::FIELD_LOAD_CURRENT;

    // Collect entities ids, bundles and languages.
    $bundles = array();
    $ids = array();
    $default_langcodes = array();
    foreach ($entities as $key => $entity) {
      $bundles[$entity->bundle()] = TRUE;
      $ids[] = $load_current ? $key : $entity->getRevisionId();
      $default_langcodes[$key] = $entity->getUntranslated()->language()->id;
    }

    // Collect impacted fields.
    $fields = array();
    foreach ($bundles as $bundle => $v) {
      foreach ($this->entityManager->getFieldDefinitions($this->entityTypeId, $bundle) as $field_name => $instance) {
        if ($instance instanceof FieldInstanceConfigInterface) {
          $fields[$field_name] = $instance->getField();
        }
      }
    }

    // Load field data.
    $langcodes = array_keys(language_list(LanguageInterface::STATE_ALL));
    foreach ($fields as $field_name => $field) {
      $table = $load_current ? static::_fieldTableName($field) : static::_fieldRevisionTableName($field);

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

        // Ensure that records for non-translatable fields having invalid
        // languages are skipped.
        if ($row->langcode == $default_langcodes[$row->entity_id] || $field->isTranslatable()) {
          if (!isset($delta_count[$row->entity_id][$row->langcode])) {
            $delta_count[$row->entity_id][$row->langcode] = 0;
          }

          if ($field->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $delta_count[$row->entity_id][$row->langcode] < $field->getCardinality()) {
            $item = array();
            // For each column declared by the field, populate the item from the
            // prefixed database column.
            foreach ($field->getColumns() as $column => $attributes) {
              $column_name = static::_fieldColumnName($field, $column);
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
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(EntityInterface $entity, $update) {
    $vid = $entity->getRevisionId();
    $id = $entity->id();
    $bundle = $entity->bundle();
    $entity_type = $entity->getEntityTypeId();
    $default_langcode = $entity->getUntranslated()->language()->id;
    $translation_langcodes = array_keys($entity->getTranslationLanguages());

    if (!isset($vid)) {
      $vid = $id;
    }

    foreach ($this->entityManager->getFieldDefinitions($entity_type, $bundle) as $field_name => $instance) {
      if (!($instance instanceof FieldInstanceConfigInterface)) {
        continue;
      }
      $field = $instance->getField();
      $table_name = static::_fieldTableName($field);
      $revision_name = static::_fieldRevisionTableName($field);

      // Delete and insert, rather than update, in case a value was added.
      if ($update) {
        // Only overwrite the field's base table if saving the default revision
        // of an entity.
        if ($entity->isDefaultRevision()) {
          $this->database->delete($table_name)
            ->condition('entity_id', $id)
            ->execute();
        }
        $this->database->delete($revision_name)
          ->condition('entity_id', $id)
          ->condition('revision_id', $vid)
          ->execute();
      }

      // Prepare the multi-insert query.
      $do_insert = FALSE;
      $columns = array('entity_id', 'revision_id', 'bundle', 'delta', 'langcode');
      foreach ($field->getColumns() as $column => $attributes) {
        $columns[] = static::_fieldColumnName($field, $column);
      }
      $query = $this->database->insert($table_name)->fields($columns);
      $revision_query = $this->database->insert($revision_name)->fields($columns);

      $langcodes = $field->isTranslatable() ? $translation_langcodes : array($default_langcode);
      foreach ($langcodes as $langcode) {
        $delta_count = 0;
        $items = $entity->getTranslation($langcode)->get($field_name);
        $items->filterEmptyItems();
        foreach ($items as $delta => $item) {
          // We now know we have someting to insert.
          $do_insert = TRUE;
          $record = array(
            'entity_id' => $id,
            'revision_id' => $vid,
            'bundle' => $bundle,
            'delta' => $delta,
            'langcode' => $langcode,
          );
          foreach ($field->getColumns() as $column => $attributes) {
            $column_name = static::_fieldColumnName($field, $column);
            // Serialize the value if specified in the column schema.
            $record[$column_name] = !empty($attributes['serialize']) ? serialize($item->$column) : $item->$column;
          }
          $query->values($record);
          $revision_query->values($record);

          if ($field->getCardinality() != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && ++$delta_count == $field->getCardinality()) {
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
        $revision_query->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems(EntityInterface $entity) {
    foreach ($this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $instance) {
      if (!($instance instanceof FieldInstanceConfigInterface)) {
        continue;
      }
      $field = $instance->getField();
      $table_name = static::_fieldTableName($field);
      $revision_name = static::_fieldRevisionTableName($field);
      $this->database->delete($table_name)
        ->condition('entity_id', $entity->id())
        ->execute();
      $this->database->delete($revision_name)
        ->condition('entity_id', $entity->id())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItemsRevision(EntityInterface $entity) {
    $vid = $entity->getRevisionId();
    if (isset($vid)) {
      foreach ($this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $instance) {
        if (!($instance instanceof FieldInstanceConfigInterface)) {
          continue;
        }
        $revision_name = static::_fieldRevisionTableName($instance->getField());
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
  public function onFieldCreate(FieldConfigInterface $field) {
    $schema = $this->_fieldSqlSchema($field);
    foreach ($schema as $name => $table) {
      $this->database->schema()->createTable($name, $table);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldUpdate(FieldConfigInterface $field) {
    $original = $field->original;

    if (!$field->hasData()) {
      // There is no data. Re-create the tables completely.

      if ($this->database->supportsTransactionalDDL()) {
        // If the database supports transactional DDL, we can go ahead and rely
        // on it. If not, we will have to rollback manually if something fails.
        $transaction = $this->database->startTransaction();
      }

      try {
        $original_schema = $this->_fieldSqlSchema($original);
        foreach ($original_schema as $name => $table) {
          $this->database->schema()->dropTable($name, $table);
        }
        $schema = $this->_fieldSqlSchema($field);
        foreach ($schema as $name => $table) {
          $this->database->schema()->createTable($name, $table);
        }
      }
      catch (\Exception $e) {
        if ($this->database->supportsTransactionalDDL()) {
          $transaction->rollback();
        }
        else {
          // Recreate tables.
          $original_schema = $this->_fieldSqlSchema($original);
          foreach ($original_schema as $name => $table) {
            if (!$this->database->schema()->tableExists($name)) {
              $this->database->schema()->createTable($name, $table);
            }
          }
        }
        throw $e;
      }
    }
    else {
      if ($field->getColumns() != $original->getColumns()) {
        throw new FieldConfigUpdateForbiddenException("The SQL storage cannot change the schema for an existing field with data.");
      }
      // There is data, so there are no column changes. Drop all the prior
      // indexes and create all the new ones, except for all the priors that
      // exist unchanged.
      $table = static::_fieldTableName($original);
      $revision_table = static::_fieldRevisionTableName($original);

      $schema = $field->getSchema();
      $original_schema = $original->getSchema();

      foreach ($original_schema['indexes'] as $name => $columns) {
        if (!isset($schema['indexes'][$name]) || $columns != $schema['indexes'][$name]) {
          $real_name = static::_fieldIndexName($field, $name);
          $this->database->schema()->dropIndex($table, $real_name);
          $this->database->schema()->dropIndex($revision_table, $real_name);
        }
      }
      $table = static::_fieldTableName($field);
      $revision_table = static::_fieldRevisionTableName($field);
      foreach ($schema['indexes'] as $name => $columns) {
        if (!isset($original_schema['indexes'][$name]) || $columns != $original_schema['indexes'][$name]) {
          $real_name = static::_fieldIndexName($field, $name);
          $real_columns = array();
          foreach ($columns as $column_name) {
            // Indexes can be specified as either a column name or an array with
            // column name and length. Allow for either case.
            if (is_array($column_name)) {
              $real_columns[] = array(
                static::_fieldColumnName($field, $column_name[0]),
                $column_name[1],
              );
            }
            else {
              $real_columns[] = static::_fieldColumnName($field, $column_name);
            }
          }
          $this->database->schema()->addIndex($table, $real_name, $real_columns);
          $this->database->schema()->addIndex($revision_table, $real_name, $real_columns);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDelete(FieldConfigInterface $field) {
    // Mark all data associated with the field for deletion.
    $table = static::_fieldTableName($field);
    $revision_table = static::_fieldRevisionTableName($field);
    $this->database->update($table)
      ->fields(array('deleted' => 1))
      ->execute();

    // Move the table to a unique name while the table contents are being
    // deleted.
    $deleted_field = clone $field;
    $deleted_field->deleted = TRUE;
    $new_table = static::_fieldTableName($deleted_field);
    $revision_new_table = static::_fieldRevisionTableName($deleted_field);
    $this->database->schema()->renameTable($table, $new_table);
    $this->database->schema()->renameTable($revision_table, $revision_new_table);
  }

  /**
   * {@inheritdoc}
   */
  public function onInstanceDelete(FieldInstanceConfigInterface $instance) {
    $field = $instance->getField();
    $table_name = static::_fieldTableName($field);
    $revision_name = static::_fieldRevisionTableName($field);
    $this->database->update($table_name)
      ->fields(array('deleted' => 1))
      ->condition('bundle', $instance->bundle)
      ->execute();
    $this->database->update($revision_name)
      ->fields(array('deleted' => 1))
      ->condition('bundle', $instance->bundle)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleRename($bundle, $bundle_new) {
    // We need to account for deleted fields and instances. The method runs
    // before the instance definitions are updated, so we need to fetch them
    // using the old bundle name.
    $instances = entity_load_multiple_by_properties('field_instance_config', array('entity_type' => $this->entityTypeId, 'bundle' => $bundle, 'include_deleted' => TRUE));
    foreach ($instances as $instance) {
      $field = $instance->getField();
      $table_name = static::_fieldTableName($field);
      $revision_name = static::_fieldRevisionTableName($field);
      $this->database->update($table_name)
        ->fields(array('bundle' => $bundle_new))
        ->condition('bundle', $bundle)
        ->execute();
      $this->database->update($revision_name)
        ->fields(array('bundle' => $bundle_new))
        ->condition('bundle', $bundle)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(EntityInterface $entity, FieldInstanceConfigInterface $instance) {
    $field = $instance->getField();
    $table_name = static::_fieldTableName($field);
    $query = $this->database->select($table_name, 't', array('fetch' => \PDO::FETCH_ASSOC))
      ->condition('entity_id', $entity->id())
      ->orderBy('delta');
    foreach ($field->getColumns() as $column_name => $data) {
      $query->addField('t', static::_fieldColumnName($field, $column_name), $column_name);
    }
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function purgeFieldItems(EntityInterface $entity, FieldInstanceConfigInterface $instance) {
    $field = $instance->getField();
    $table_name = static::_fieldTableName($field);
    $revision_name = static::_fieldRevisionTableName($field);
    $this->database->delete($table_name)
      ->condition('entity_id', $entity->id())
      ->execute();
    $this->database->delete($revision_name)
      ->condition('entity_id', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldPurge(FieldConfigInterface $field) {
    $table_name = static::_fieldTableName($field);
    $revision_name = static::_fieldRevisionTableName($field);
    $this->database->schema()->dropTable($table_name);
    $this->database->schema()->dropTable($revision_name);
  }

  /**
   * Gets the SQL table schema.
   *
   * @private Calling this function circumvents the entity system and is
   * strongly discouraged. This function is not considered part of the public
   * API and modules relying on it might break even in minor releases.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field object
   * @param array $schema
   *   The field schema array. Mandatory for upgrades, omit otherwise.
   *
   * @return array
   *   The same as a hook_schema() implementation for the data and the
   *   revision tables.
   *
   * @see hook_schema()
   */
  public static function _fieldSqlSchema(FieldConfigInterface $field, array $schema = NULL) {
    if ($field->deleted) {
      $description_current = "Data storage for deleted field {$field->uuid()} ({$field->entity_type}, {$field->getName()}).";
      $description_revision = "Revision archive storage for deleted field {$field->uuid()} ({$field->entity_type}, {$field->getName()}).";
    }
    else {
      $description_current = "Data storage for {$field->entity_type} field {$field->getName()}.";
      $description_revision = "Revision archive storage for {$field->entity_type} field {$field->getName()}.";
    }

    $entity_type_id = $field->entity_type;
    $entity_manager = \Drupal::entityManager();
    $entity_type = $entity_manager->getDefinition($entity_type_id);
    $definitions = $entity_manager->getBaseFieldDefinitions($entity_type_id);

    // Define the entity ID schema based on the field definitions.
    $id_definition = $definitions[$entity_type->getKey('id')];
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
        'type' => 'varchar',
        'length' => 128,
        'not null' => TRUE,
        'description' => 'The entity id this data is attached to',
      );
    }

    // Define the revision ID schema, default to integer if there is no revision
    // ID.
    $revision_id_definition = $entity_type->hasKey('revision') ? $definitions[$entity_type->getKey('revision')] : NULL;
    if (!$revision_id_definition || $revision_id_definition->getType() == 'integer') {
      $revision_id_schema = array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => FALSE,
        'description' => 'The entity revision id this data is attached to, or NULL if the entity type is not versioned',
      );
    }
    else {
      $revision_id_schema = array(
        'type' => 'varchar',
        'length' => 128,
        'not null' => FALSE,
        'description' => 'The entity revision id this data is attached to, or NULL if the entity type is not versioned',
      );
    }

    $current = array(
      'description' => $description_current,
      'fields' => array(
        'bundle' => array(
          'type' => 'varchar',
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
          'type' => 'varchar',
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
        'deleted' => array('deleted'),
        'entity_id' => array('entity_id'),
        'revision_id' => array('revision_id'),
        'langcode' => array('langcode'),
      ),
    );

    if (!$schema) {
      $schema = $field->getSchema();
    }

    // Add field columns.
    foreach ($schema['columns'] as $column_name => $attributes) {
      $real_name = static::_fieldColumnName($field, $column_name);
      $current['fields'][$real_name] = $attributes;
    }

    // Add unique keys.
    foreach ($schema['unique keys'] as $unique_key_name => $columns) {
      $real_name = static::_fieldIndexName($field, $unique_key_name);
      foreach ($columns as $column_name) {
        $current['unique keys'][$real_name][] = static::_fieldColumnName($field, $column_name);
      }
    }

    // Add indexes.
    foreach ($schema['indexes'] as $index_name => $columns) {
      $real_name = static::_fieldIndexName($field, $index_name);
      foreach ($columns as $column_name) {
        // Indexes can be specified as either a column name or an array with
        // column name and length. Allow for either case.
        if (is_array($column_name)) {
          $current['indexes'][$real_name][] = array(
            static::_fieldColumnName($field, $column_name[0]),
            $column_name[1],
          );
        }
        else {
          $current['indexes'][$real_name][] = static::_fieldColumnName($field, $column_name);
        }
      }
    }

    // Add foreign keys.
    foreach ($schema['foreign keys'] as $specifier => $specification) {
      $real_name = static::_fieldIndexName($field, $specifier);
      $current['foreign keys'][$real_name]['table'] = $specification['table'];
      foreach ($specification['columns'] as $column_name => $referenced) {
        $sql_storage_column = static::_fieldColumnName($field, $column_name);
        $current['foreign keys'][$real_name]['columns'][$sql_storage_column] = $referenced;
      }
    }

    // Construct the revision table.
    $revision = $current;
    $revision['description'] = $description_revision;
    $revision['primary key'] = array('entity_id', 'revision_id', 'deleted', 'delta', 'langcode');
    $revision['fields']['revision_id']['not null'] = TRUE;
    $revision['fields']['revision_id']['description'] = 'The entity revision id this data is attached to';

    return array(
      static::_fieldTableName($field) => $current,
      static::_fieldRevisionTableName($field) => $revision,
    );
  }

  /**
   * Generates a table name for a field data table.
   *
   * @private Calling this function circumvents the entity system and is
   * strongly discouraged. This function is not considered part of the public
   * API and modules relying on it might break even in minor releases. Only
   * call this function to write a query that \Drupal::entityQuery() does not
   * support. Always call entity_load() before using the data found in the
   * table.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field object.
   *
   * @return string
   *   A string containing the generated name for the database table.
   *
   */
  static public function _fieldTableName(FieldConfigInterface $field) {
    if ($field->deleted) {
      // When a field is a deleted, the table is renamed to
      // {field_deleted_data_FIELD_UUID}. To make sure we don't end up with
      // table names longer than 64 characters, we hash the uuid and return the
      // first 10 characters so we end up with a short unique ID.
      return "field_deleted_data_" . substr(hash('sha256', $field->uuid()), 0, 10);
    }
    else {
      return static::_generateFieldTableName($field, FALSE);
    }
  }

  /**
   * Generates a table name for a field revision archive table.
   *
   * @private Calling this function circumvents the entity system and is
   * strongly discouraged. This function is not considered part of the public
   * API and modules relying on it might break even in minor releases. Only
   * call this function to write a query that \Drupal::entityQuery() does not
   * support. Always call entity_load() before using the data found in the
   * table.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field object.
   *
   * @return string
   *   A string containing the generated name for the database table.
   */
  static public function _fieldRevisionTableName(FieldConfigInterface $field) {
    if ($field->deleted) {
      // When a field is a deleted, the table is renamed to
      // {field_deleted_revision_FIELD_UUID}. To make sure we don't end up with
      // table names longer than 64 characters, we hash the uuid and return the
      // first 10 characters so we end up with a short unique ID.
      return "field_deleted_revision_" . substr(hash('sha256', $field->uuid()), 0, 10);
    }
    else {
      return static::_generateFieldTableName($field, TRUE);
    }
  }

  /**
   * Generates a safe and unanbiguous field table name.
   *
   * The method accounts for a maximum table name length of 64 characters, and
   * takes care of disambiguation.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field object.
   * @param bool $revision
   *   TRUE for revision table, FALSE otherwise.
   *
   * @return string
   *   The final table name.
   */
  static protected function _generateFieldTableName(FieldConfigInterface $field, $revision) {
    $separator = $revision ? '_revision__' : '__';
    $table_name = $field->entity_type . $separator .  $field->name;
    // Limit the string to 48 characters, keeping a 16 characters margin for db
    // prefixes.
    if (strlen($table_name) > 48) {
      // Use a shorter separator, a truncated entity_type, and a hash of the
      // field UUID.
      $separator = $revision ? '_r__' : '__';
      // Truncate to the same length for the current and revision tables.
      $entity_type = substr($field->entity_type, 0, 34);
      $field_hash = substr(hash('sha256', $field->uuid), 0, 10);
      $table_name = $entity_type . $separator . $field_hash;
    }
    return $table_name;
  }

  /**
   * Generates an index name for a field data table.
   *
   * @private Calling this function circumvents the entity system and is
   * strongly discouraged. This function is not considered part of the public
   * API and modules relying on it might break even in minor releases.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field structure
   * @param string $index
   *   The name of the index.
   *
   * @return string
   *   A string containing a generated index name for a field data table that is
   *   unique among all other fields.
   */
  static public function _fieldIndexName(FieldConfigInterface $field, $index) {
    return $field->getName() . '_' . $index;
  }

  /**
   * Generates a column name for a field data table.
   *
   * @private Calling this function circumvents the entity system and is
   * strongly discouraged. This function is not considered part of the public
   * API and modules relying on it might break even in minor releases. Only
   * call this function to write a query that \Drupal::entityQuery() does not
   * support. Always call entity_load() before using the data found in the
   * table.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field object.
   * @param string $column
   *   The name of the column.
   *
   * @return string
   *   A string containing a generated column name for a field data table that is
   *   unique among all other fields.
   */
  static public function _fieldColumnName(FieldConfigInterface $field, $column) {
    return in_array($column, FieldConfig::getReservedColumns()) ? $column : $field->getName() . '_' . $column;
  }

}
