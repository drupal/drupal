<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\DatabaseStorageController.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\Language;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Uuid;
use Drupal\field\FieldInfo;
use Drupal\field\FieldConfigUpdateForbiddenException;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldInstanceConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base entity controller class.
 *
 * This class only supports bare, non-content entities.
 */
class DatabaseStorageController extends EntityStorageControllerBase {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Whether this entity type should use the static cache.
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
   * The field info object.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('uuid')
    );
  }

  /**
   * Constructs a DatabaseStorageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, UuidInterface $uuid_service) {
    parent::__construct($entity_type);

    $this->database = $database;
    $this->uuidService = $uuid_service;

    // Check if the entity type supports IDs.
    $this->idKey = $this->entityType->getKey('id');

    // Check if the entity type supports UUIDs.
    $this->uuidKey = $this->entityType->getKey('uuid');
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
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

      if ($class = $this->entityType->getClass()) {
        // We provide the necessary arguments for PDO to create objects of the
        // specified entity class.
        // @see \Drupal\Core\Entity\EntityInterface::__construct()
        $query_result->setFetchMode(\PDO::FETCH_CLASS, $class, array(array(), $this->entityTypeId));
      }
      $queried_entities = $query_result->fetchAllAssoc($this->idKey);
    }

    // Pass all entities loaded from the database through $this->postLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($queried_entities)) {
      $this->postLoad($queried_entities);
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
   * {@inheritdoc}
   */
  public function load($id) {
    $entities = $this->loadMultiple(array($id));
    return isset($entities[$id]) ? $entities[$id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    throw new \Exception('Database storage controller does not support revisions.');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
    throw new \Exception('Database storage controller does not support revisions.');
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = array()) {
    // Build a query to fetch the entity IDs.
    $entity_query = \Drupal::entityQuery($this->entityTypeId);
    $this->buildPropertyQuery($entity_query, $values);
    $result = $entity_query->execute();
    return $result ? $this->loadMultiple($result) : array();
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
   * @param array|null $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return SelectQuery
   *   A SelectQuery object for loading the entity.
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = $this->database->select($this->entityType->getBaseTable(), 'base');

    $query->addTag($this->entityTypeId . '_load_multiple');

    // Add fields from the {entity} table.
    $entity_fields = drupal_schema_fields_sql($this->entityType->getBaseTable());
    $query->fields('base', $entity_fields);

    if ($ids) {
      $query->condition("base.{$this->idKey}", $ids, 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = array()) {
    $entity_class = $this->entityType->getClass();
    $entity_class::preCreate($this, $values);

    $entity = new $entity_class($values, $this->entityTypeId);

    // Assign a new UUID if there is none yet.
    if ($this->uuidKey && !isset($entity->{$this->uuidKey})) {
      $entity->{$this->uuidKey} = $this->uuidService->generate();
    }
    $entity->postCreate($this);

    // Modules might need to add or change the data initially held by the new
    // entity object, for instance to fill-in default values.
    $this->invokeHook('create', $entity);

    return $entity;
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
      $entity_class = $this->entityType->getClass();
      $entity_class::preDelete($this, $entities);
      foreach ($entities as $entity) {
        $this->invokeHook('predelete', $entity);
      }
      $ids = array_keys($entities);

      $this->database->delete($this->entityType->getBaseTable())
        ->condition($this->idKey, $ids, 'IN')
        ->execute();

      // Reset the cache as soon as the changes have been applied.
      $this->resetCache($ids);

      $entity_class::postDelete($this, $entities);
      foreach ($entities as $entity) {
        $this->invokeHook('delete', $entity);
      }
      // Ignore slave server temporarily.
      db_ignore_slave();
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
  public function save(EntityInterface $entity) {
    $transaction = $this->database->startTransaction();
    try {
      // Load the stored entity, if any.
      if (!$entity->isNew() && !isset($entity->original)) {
        $entity->original = entity_load_unchanged($this->entityTypeId, $entity->id());
      }

      $entity->preSave($this);
      $this->invokeHook('presave', $entity);

      if (!$entity->isNew()) {
        $return = drupal_write_record($this->entityType->getBaseTable(), $entity, $this->idKey);
        $this->resetCache(array($entity->id()));
        $entity->postSave($this, TRUE);
        $this->invokeHook('update', $entity);
      }
      else {
        $return = drupal_write_record($this->entityType->getBaseTable(), $entity);
        // Reset general caches, but keep caches specific to certain entities.
        $this->resetCache(array());

        $entity->enforceIsNew(FALSE);
        $entity->postSave($this, FALSE);
        $this->invokeHook('insert', $entity);
      }

      // Ignore slave server temporarily.
      db_ignore_slave();
      unset($entity->original);

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
  public function getQueryServiceName() {
    return 'entity.query.sql';
  }

}
