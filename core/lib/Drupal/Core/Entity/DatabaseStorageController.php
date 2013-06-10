<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\DatabaseStorageController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use PDO;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Component\Uuid\Uuid;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Connection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base entity controller class.
 *
 * Default implementation of Drupal\Core\Entity\EntityStorageControllerInterface.
 *
 * This class can be used as-is by most simple entity types. Entity types
 * requiring special handling can extend the class.
 */
class DatabaseStorageController implements EntityStorageControllerInterface, EntityControllerInterface {

  /**
   * Static cache of entities.
   *
   * @var array
   */
  protected $entityCache;

  /**
   * Entity type for this controller instance.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Array of information about the entity.
   *
   * @var array
   *
   * @see entity_get_info()
   */
  protected $entityInfo;

  /**
   * An array of field information, i.e. containing definitions.
   *
   * @var array
   *
   * @see hook_entity_field_info()
   */
  protected $entityFieldInfo;

  /**
   * Static cache of field definitions per bundle.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * Additional arguments to pass to hook_TYPE_load().
   *
   * Set before calling Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   *
   * @var array
   */
  protected $hookLoadArguments;

  /**
   * Name of the entity's ID field in the entity database table.
   *
   * @var string
   */
  protected $idKey;

  /**
   * Name of entity's UUID database table field, if it supports UUIDs.
   *
   * Has the value FALSE if this entity does not use UUIDs.
   *
   * @var string
   */
  protected $uuidKey;

  /**
   * Name of entity's revision database table field, if it supports revisions.
   *
   * Has the value FALSE if this entity does not use revisions.
   *
   * @var string
   */
  protected $revisionKey;

  /**
   * The table that stores revisions, if the entity supports revisions.
   *
   * @var string
   */
  protected $revisionTable;

  /**
   * Whether this entity type should use the static cache.
   *
   * Set by entity info.
   *
   * @var boolean
   */
  protected $cache;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('database')
    );
  }

  /**
   * Constructs a DatabaseStorageController object.
   *
   * @param string $entity_type
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct($entity_type, array $entity_info, Connection $database) {
    $this->database = $database;
    $this->entityType = $entity_type;
    $this->entityInfo = $entity_info;
    $this->entityCache = array();
    $this->hookLoadArguments = array();

    // Check if the entity type supports IDs.
    if (isset($this->entityInfo['entity_keys']['id'])) {
      $this->idKey = $this->entityInfo['entity_keys']['id'];
    }
    else {
      $this->idKey = FALSE;
    }

    // Check if the entity type supports UUIDs.
    if (!empty($this->entityInfo['entity_keys']['uuid'])) {
      $this->uuidKey = $this->entityInfo['entity_keys']['uuid'];
    }
    else {
      $this->uuidKey = FALSE;
    }

    // Check if the entity type supports revisions.
    if (!empty($this->entityInfo['entity_keys']['revision'])) {
      $this->revisionKey = $this->entityInfo['entity_keys']['revision'];
      $this->revisionTable = $this->entityInfo['revision_table'];
    }
    else {
      $this->revisionKey = FALSE;
    }

    // Check if the entity type supports static caching of loaded entities.
    $this->cache = !empty($this->entityInfo['static_cache']);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    if (isset($ids)) {
      foreach ($ids as $id) {
        unset($this->entityCache[$id]);
      }
    }
    else {
      $this->entityCache = array();
      $this->entityFieldInfo = NULL;
      $this->fieldDefinitions = array();
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::load().
   */
  public function load(array $ids = NULL) {
    $entities = array();

    // Create a new variable which is either a prepared version of the $ids
    // array for later comparison with the entity cache, or FALSE if no $ids
    // were passed. The $ids array is reduced as items are loaded from cache,
    // and we need to know if it's empty for this reason to avoid querying the
    // database when all requested entities are loaded from cache.
    $passed_ids = !empty($ids) ? array_flip($ids) : FALSE;
    // Try to load entities from the static cache, if the entity type supports
    // static caching.
    if ($this->cache && $ids) {
      $entities += $this->cacheGet($ids);
      // If any entities were loaded, remove them from the ids still to load.
      if ($passed_ids) {
        $ids = array_keys(array_diff_key($passed_ids, $entities));
      }
    }

    // Load any remaining entities from the database. This is the case if $ids
    // is set to NULL (so we load all entities) or if there are any ids left to
    // load.
    if ($ids === NULL || $ids) {
      // Build and execute the query.
      $query_result = $this->buildQuery($ids)->execute();

      if (!empty($this->entityInfo['class'])) {
        // We provide the necessary arguments for PDO to create objects of the
        // specified entity class.
        // @see Drupal\Core\Entity\EntityInterface::__construct()
        $query_result->setFetchMode(PDO::FETCH_CLASS, $this->entityInfo['class'], array(array(), $this->entityType));
      }
      $queried_entities = $query_result->fetchAllAssoc($this->idKey);
    }

    // Pass all entities loaded from the database through $this->attachLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($queried_entities)) {
      $this->attachLoad($queried_entities);
      $entities += $queried_entities;
    }

    if ($this->cache) {
      // Add entities to the cache.
      if (!empty($queried_entities)) {
        $this->cacheSet($queried_entities);
      }
    }

    // Ensure that the returned array is ordered the same as the original
    // $ids array if this was passed in and remove any invalid ids.
    if ($passed_ids) {
      // Remove any invalid ids from the array.
      $passed_ids = array_intersect_key($passed_ids, $entities);
      foreach ($entities as $entity) {
        $passed_ids[$entity->id()] = $entity;
      }
      $entities = $passed_ids;
    }

    return $entities;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::loadUnchanged()
   */
  public function loadUnchanged($id) {
    $this->resetCache(array($id));
    $result = $this->load(array($id));
    return reset($result);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::loadRevision().
   */
  public function loadRevision($revision_id) {
    // Build and execute the query.
    $query_result = $this->buildQuery(array(), $revision_id)->execute();

    if (!empty($this->entityInfo['class'])) {
      // We provide the necessary arguments for PDO to create objects of the
      // specified entity class.
      // @see Drupal\Core\Entity\EntityInterface::__construct()
      $query_result->setFetchMode(PDO::FETCH_CLASS, $this->entityInfo['class'], array(array(), $this->entityType));
    }
    $queried_entities = $query_result->fetchAllAssoc($this->idKey);

    // Pass the loaded entities from the database through $this->attachLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($queried_entities)) {
      $this->attachLoad($queried_entities, $revision_id);
    }
    return reset($queried_entities);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::deleteRevision().
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
      $this->invokeHook('revision_delete', $revision);
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::loadByProperties().
   */
  public function loadByProperties(array $values = array()) {
    // Build a query to fetch the entity IDs.
    $entity_query = \Drupal::entityQuery($this->entityType);
    $this->buildPropertyQuery($entity_query, $values);
    $result = $entity_query->execute();
    return $result ? $this->load($result) : array();
  }

  /**
   * Builds an entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $entity_query
   *   EntityQuery instance.
   * @param array $values
   *   An associative array of properties of the entity, where the keys are the
   *   property names and the values are the values those properties must have.
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    foreach ($values as $name => $value) {
      $entity_query->condition($name, $value);
    }
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
   * See Drupal\comment\CommentStorageController::buildQuery() for an example.
   *
   * @param array|null $ids
   *   An array of entity IDs, or NULL to load all entities.
   * @param $revision_id
   *   The ID of the revision to load, or FALSE if this query is asking for the
   *   most current revision(s).
   *
   * @return SelectQuery
   *   A SelectQuery object for loading the entity.
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = $this->database->select($this->entityInfo['base_table'], 'base');

    $query->addTag($this->entityType . '_load_multiple');

    if ($revision_id) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->idKey} = base.{$this->idKey} AND revision.{$this->revisionKey} = :revisionId", array(':revisionId' => $revision_id));
    }
    elseif ($this->revisionKey) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->revisionKey} = base.{$this->revisionKey}");
    }

    // Add fields from the {entity} table.
    $entity_fields = drupal_schema_fields_sql($this->entityInfo['base_table']);

    if ($this->revisionKey) {
      // Add all fields from the {entity_revision} table.
      $entity_revision_fields = drupal_map_assoc(drupal_schema_fields_sql($this->entityInfo['revision_table']));
      // The id field is provided by entity, so remove it.
      unset($entity_revision_fields[$this->idKey]);

      // Remove all fields from the base table that are also fields by the same
      // name in the revision table.
      $entity_field_keys = array_flip($entity_fields);
      foreach ($entity_revision_fields as $key => $name) {
        if (isset($entity_field_keys[$name])) {
          unset($entity_fields[$entity_field_keys[$name]]);
        }
      }
      $query->fields('revision', $entity_revision_fields);

      // Compare revision id of the base and revision table, if equal then this
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
   * Attaches data to entities upon loading.
   *
   * This will attach fields, if the entity is fieldable. It calls
   * hook_entity_load() for modules which need to add data to all entities.
   * It also calls hook_TYPE_load() on the loaded entities. For example
   * hook_node_load() or hook_user_load(). If your hook_TYPE_load()
   * expects special parameters apart from the queried entities, you can set
   * $this->hookLoadArguments prior to calling the method.
   * See Drupal\node\NodeStorageController::attachLoad() for an example.
   *
   * @param $queried_entities
   *   Associative array of query results, keyed on the entity ID.
   * @param $load_revision
   *   (optional) TRUE if the revision should be loaded, defaults to FALSE.
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    // Attach fields.
    if ($this->entityInfo['fieldable']) {
      if ($load_revision) {
        field_attach_load_revision($this->entityType, $queried_entities);
      }
      else {
        field_attach_load($this->entityType, $queried_entities);
      }
    }

    // Call hook_entity_load().
    foreach (module_implements('entity_load') as $module) {
      $function = $module . '_entity_load';
      $function($queried_entities, $this->entityType);
    }
    // Call hook_TYPE_load(). The first argument for hook_TYPE_load() are
    // always the queried entities, followed by additional arguments set in
    // $this->hookLoadArguments.
    $args = array_merge(array($queried_entities), $this->hookLoadArguments);
    foreach (module_implements($this->entityType . '_load') as $module) {
      call_user_func_array($module . '_' . $this->entityType . '_load', $args);
    }
  }

  /**
   * Gets entities from the static cache.
   *
   * @param $ids
   *   If not empty, return entities that match these IDs.
   *
   * @return
   *   Array of entities from the entity cache.
   */
  protected function cacheGet($ids) {
    $entities = array();
    // Load any available entities from the internal cache.
    if (!empty($this->entityCache)) {
      $entities += array_intersect_key($this->entityCache, array_flip($ids));
    }
    return $entities;
  }

  /**
   * Stores entities in the static entity cache.
   *
   * @param $entities
   *   Entities to store in the cache.
   */
  protected function cacheSet($entities) {
    $this->entityCache += $entities;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::create().
   */
  public function create(array $values) {
    $class = $this->entityInfo['class'];

    $entity = new $class($values, $this->entityType);

    // Assign a new UUID if there is none yet.
    if ($this->uuidKey && !isset($entity->{$this->uuidKey})) {
      $uuid = new Uuid();
      $entity->{$this->uuidKey} = $uuid->generate();
    }

    // Modules might need to add or change the data initially held by the new
    // entity object, for instance to fill-in default values.
    $this->invokeHook('create', $entity);

    return $entity;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::delete().
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }
    $transaction = $this->database->startTransaction();

    try {
      $this->preDelete($entities);
      foreach ($entities as $id => $entity) {
        $this->invokeHook('predelete', $entity);
      }
      $ids = array_keys($entities);

      $this->database->delete($this->entityInfo['base_table'])
        ->condition($this->idKey, $ids, 'IN')
        ->execute();

      if ($this->revisionKey) {
        $this->database->delete($this->revisionTable)
          ->condition($this->idKey, $ids, 'IN')
          ->execute();
      }

      // Reset the cache as soon as the changes have been applied.
      $this->resetCache($ids);

      $this->postDelete($entities);
      foreach ($entities as $id => $entity) {
        $this->invokeHook('delete', $entity);
      }
      // Ignore slave server temporarily.
      db_ignore_slave();
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::save().
   */
  public function save(EntityInterface $entity) {
    $transaction = $this->database->startTransaction();
    try {
      // Load the stored entity, if any.
      if (!$entity->isNew() && !isset($entity->original)) {
        $entity->original = entity_load_unchanged($this->entityType, $entity->id());
      }

      $this->preSave($entity);
      $this->invokeHook('presave', $entity);

      if (!$entity->isNew()) {
        if ($entity->isDefaultRevision()) {
          $return = drupal_write_record($this->entityInfo['base_table'], $entity, $this->idKey);
        }
        else {
          // @todo, should a different value be returned when saving an entity
          // with $isDefaultRevision = FALSE?
          $return = FALSE;
        }
        if ($this->revisionKey) {
          $this->saveRevision($entity);
        }
        $this->resetCache(array($entity->id()));
        $this->postSave($entity, TRUE);
        $this->invokeHook('update', $entity);
      }
      else {
        $return = drupal_write_record($this->entityInfo['base_table'], $entity);
        if ($this->revisionKey) {
          $this->saveRevision($entity);
        }
        // Reset general caches, but keep caches specific to certain entities.
        $this->resetCache(array());

        $entity->enforceIsNew(FALSE);
        $this->postSave($entity, FALSE);
        $this->invokeHook('insert', $entity);
      }

      // Ignore slave server temporarily.
      db_ignore_slave();
      unset($entity->original);

      return $return;
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Saves an entity revision.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  protected function saveRevision(EntityInterface $entity) {
    // Convert the entity into an array as it might not have the same properties
    // as the entity, it is just a raw structure.
    $record = (array) $entity;

    // When saving a new revision, set any existing revision ID to NULL so as to
    // ensure that a new revision will actually be created.
    if ($entity->isNewRevision() && $record[$this->revisionKey]) {
      $record[$this->revisionKey] = NULL;
    }

    // Cast to object as preSaveRevision() expects one to be compatible with the
    // upcoming NG storage controller.
    $record = (object) $record;
    $this->preSaveRevision($record, $entity);
    $record = (array) $record;

    if ($entity->isNewRevision()) {
      drupal_write_record($this->revisionTable, $record);
      if ($entity->isDefaultRevision()) {
        $this->database->update($this->entityInfo['base_table'])
          ->fields(array($this->revisionKey => $record[$this->revisionKey]))
          ->condition($this->idKey, $entity->id())
          ->execute();
      }
      $entity->setNewRevision(FALSE);
    }
    else {
      drupal_write_record($this->revisionTable, $record, $this->revisionKey);
    }
    // Make sure to update the new revision key for the entity.
    $entity->{$this->revisionKey} = $record[$this->revisionKey];
  }

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   */
  protected function preSave(EntityInterface $entity) { }

  /**
   * Acts on a saved entity before the insert or update hook is invoked.
   *
   * Used after the entity is saved, but before invoking the insert or update
   * hook.
   *
   * @param $update
   *   (bool) TRUE if the entity has been updated, or FALSE if it has been
   *   inserted.
   */
  protected function postSave(EntityInterface $entity, $update) { }

  /**
   * Acts on entities before they are deleted.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   */
  protected function preDelete($entities) { }

  /**
   * Acts on deleted entities before the delete hook is invoked.
   *
   * Used after the entities are deleted but before invoking the delete hook.
   */
  protected function postDelete($entities) { }

  /**
   * Act on a revision before being saved.
   *
   * @param \stdClass $record
   *   The revision object.
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  protected function preSaveRevision(\stdClass $record, EntityInterface $entity) { }

  /**
   * Invokes a hook on behalf of the entity.
   *
   * @param $hook
   *   One of 'presave', 'insert', 'update', 'predelete', or 'delete'.
   * @param $entity
   *   The entity object.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    $function = 'field_attach_' . $hook;
    // @todo: field_attach_delete_revision() is named the wrong way round,
    // consider renaming it.
    if ($function == 'field_attach_revision_delete') {
      $function = 'field_attach_delete_revision';
    }
    if (!empty($this->entityInfo['fieldable']) && function_exists($function)) {
      $function($entity);
    }
    // Invoke the hook.
    module_invoke_all($this->entityType . '_' . $hook, $entity);
    // Invoke the respective entity-level hook.
    module_invoke_all('entity_' . $hook, $entity, $this->entityType);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::getFieldDefinitions().
   */
  public function getFieldDefinitions(array $constraints) {
    if (!isset($this->entityFieldInfo)) {
      // First, try to load from cache.
      $cid = 'entity_field_definitions:' . $this->entityType . ':' . language(Language::TYPE_INTERFACE)->langcode;
      if ($cache = cache()->get($cid)) {
        $this->entityFieldInfo = $cache->data;
      }
      else {
        $this->entityFieldInfo = array(
          'definitions' => $this->baseFieldDefinitions(),
          // Contains definitions of optional (per-bundle) fields.
          'optional' => array(),
          // An array keyed by bundle name containing the optional fields added
          // by the bundle.
          'bundle map' => array(),
        );

        // Invoke hooks.
        $result = module_invoke_all($this->entityType . '_property_info');
        $this->entityFieldInfo = NestedArray::mergeDeep($this->entityFieldInfo, $result);
        $result = module_invoke_all('entity_field_info', $this->entityType);
        $this->entityFieldInfo = NestedArray::mergeDeep($this->entityFieldInfo, $result);

        $hooks = array('entity_field_info', $this->entityType . '_property_info');
        drupal_alter($hooks, $this->entityFieldInfo, $this->entityType);

        // Enforce fields to be multiple by default.
        foreach ($this->entityFieldInfo['definitions'] as &$definition) {
          $definition['list'] = TRUE;
        }
        foreach ($this->entityFieldInfo['optional'] as &$definition) {
          $definition['list'] = TRUE;
        }
        cache()->set($cid, $this->entityFieldInfo, CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));
      }
    }

    $bundle = !empty($constraints['Bundle']) ? $constraints['Bundle'] : FALSE;

    // Add in per-bundle fields.
    if (!isset($this->fieldDefinitions[$bundle])) {
      $this->fieldDefinitions[$bundle] = $this->entityFieldInfo['definitions'];

      if ($bundle && isset($this->entityFieldInfo['bundle map'][$bundle])) {
        $this->fieldDefinitions[$bundle] += array_intersect_key($this->entityFieldInfo['optional'], array_flip($this->entityFieldInfo['bundle map'][$bundle]));
      }
    }
    return $this->fieldDefinitions[$bundle];
  }

  /**
   * Defines the base properties of the entity type.
   *
   * @todo: Define abstract once all entity types have been converted.
   */
  public function baseFieldDefinitions() {
    return array();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::getQueryServiceName().
   */
  public function getQueryServiceName() {
    return 'entity.query.field_sql_storage';
  }
}
