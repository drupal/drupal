<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityStorageBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * A base entity storage class.
 */
abstract class EntityStorageBase extends EntityHandlerBase implements EntityStorageInterface, EntityHandlerInterface {

  /**
   * Static cache of entities, keyed by entity ID.
   *
   * @var array
   */
  protected $entities = array();

  /**
   * Entity type ID for this storage.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * The following code returns the same object:
   * @code
   * \Drupal::entityManager()->getDefinition($this->entityTypeId)
   * @endcode
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

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
   * The name of the entity langcode property.
   *
   * @var string
   */
  protected $langcodeKey;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Name of the entity class.
   *
   * @var string
   */
  protected $entityClass;

  /**
   * Constructs an EntityStorageBase instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->idKey = $this->entityType->getKey('id');
    $this->uuidKey = $this->entityType->getKey('uuid');
    $this->langcodeKey = $this->entityType->getKey('langcode');
    $this->entityClass = $this->entityType->getClass();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function loadUnchanged($id) {
    $this->resetCache(array($id));
    return $this->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    if ($this->entityType->isStaticallyCacheable() && isset($ids)) {
      foreach ($ids as $id) {
        unset($this->entities[$id]);
      }
    }
    else {
      $this->entities = array();
    }
  }

  /**
   * Gets entities from the static cache.
   *
   * @param array $ids
   *   If not empty, return entities that match these IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entities from the entity cache.
   */
  protected function getFromStaticCache(array $ids) {
    $entities = array();
    // Load any available entities from the internal cache.
    if ($this->entityType->isStaticallyCacheable() && !empty($this->entities)) {
      $entities += array_intersect_key($this->entities, array_flip($ids));
    }
    return $entities;
  }

  /**
   * Stores entities in the static entity cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Entities to store in the cache.
   */
  protected function setStaticCache(array $entities) {
    if ($this->entityType->isStaticallyCacheable()) {
      $this->entities += $entities;
    }
  }

  /**
   * Invokes a hook on behalf of the entity.
   *
   * @param string $hook
   *   One of 'presave', 'insert', 'update', 'predelete', 'delete', or
   *  'revision_delete'.
   * @param \Drupal\Core\Entity\EntityInterface  $entity
   *   The entity object.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    // Invoke the hook.
    $this->moduleHandler()->invokeAll($this->entityTypeId . '_' . $hook, array($entity));
    // Invoke the respective entity-level hook.
    $this->moduleHandler()->invokeAll('entity_' . $hook, array($entity));
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = array()) {
    $entity_class = $this->entityClass;
    $entity_class::preCreate($this, $values);

    // Assign a new UUID if there is none yet.
    if ($this->uuidKey && $this->uuidService && !isset($values[$this->uuidKey])) {
      $values[$this->uuidKey] = $this->uuidService->generate();
    }

    $entity = $this->doCreate($values);
    $entity->enforceIsNew();

    $entity->postCreate($this);

    // Modules might need to add or change the data initially held by the new
    // entity object, for instance to fill-in default values.
    $this->invokeHook('create', $entity);

    return $entity;
  }

  /**
   * Performs storage-specific creation of entities.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function doCreate(array $values) {
    return new $this->entityClass($values, $this->entityTypeId);
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
    if ($this->entityType->isStaticallyCacheable() && $ids) {
      $entities += $this->getFromStaticCache($ids);
      // If any entities were loaded, remove them from the ids still to load.
      if ($passed_ids) {
        $ids = array_keys(array_diff_key($passed_ids, $entities));
      }
    }

    // Load any remaining entities from the database. This is the case if $ids
    // is set to NULL (so we load all entities) or if there are any ids left to
    // load.
    if ($ids === NULL || $ids) {
      $queried_entities = $this->doLoadMultiple($ids);
    }

    // Pass all entities loaded from the database through $this->postLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($queried_entities)) {
      $this->postLoad($queried_entities);
      $entities += $queried_entities;
    }

    if ($this->entityType->isStaticallyCacheable()) {
      // Add entities to the cache.
      if (!empty($queried_entities)) {
        $this->setStaticCache($queried_entities);
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
   * Performs storage-specific loading of entities.
   *
   * Override this method to add custom functionality directly after loading.
   * This is always called, while self::postLoad() is only called when there are
   * actual results.
   *
   * @param array|null $ids
   *   (optional) An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Associative array of entities, keyed on the entity ID.
   */
  abstract protected function doLoadMultiple(array $ids = NULL);

  /**
   * Attaches data to entities upon loading.
   *
   * @param array $entities
   *   Associative array of query results, keyed on the entity ID.
   */
  protected function postLoad(array &$entities) {
    $entity_class = $this->entityClass;
    $entity_class::postLoad($this, $entities);
    // Call hook_entity_load().
    foreach ($this->moduleHandler()->getImplementations('entity_load') as $module) {
      $function = $module . '_entity_load';
      $function($entities, $this->entityTypeId);
    }
    // Call hook_TYPE_load().
    foreach ($this->moduleHandler()->getImplementations($this->entityTypeId . '_load') as $module) {
      $function = $module . '_' . $this->entityTypeId . '_load';
      $function($entities);
    }
  }

  /**
   * Maps from storage records to entity objects.
   *
   * @param array $records
   *   Associative array of query results, keyed on the entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records) {
    $entities = array();
    foreach ($records as $record) {
      $entity = new $this->entityClass($record, $this->entityTypeId);
      $entities[$entity->id()] = $entity;
    }
    return $entities;
  }

  /**
   * Determines if this entity already exists in storage.
   *
   * @param int|string $id
   *   The original entity ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @return bool
   */
  abstract protected function has($id, EntityInterface $entity);

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no entities were passed, do nothing.
      return;
    }

    // Allow code to run before deleting.
    $entity_class = $this->entityClass;
    $entity_class::preDelete($this, $entities);
    foreach ($entities as $entity) {
      $this->invokeHook('predelete', $entity);
    }

    // Perform the delete and reset the static cache for the deleted entities.
    $this->doDelete($entities);
    $this->resetCache(array_keys($entities));

    // Allow code to run after deleting.
    $entity_class::postDelete($this, $entities);
    foreach ($entities as $entity) {
      $this->invokeHook('delete', $entity);
    }
  }

  /**
   * Performs storage-specific entity deletion.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects to delete.
   */
  abstract protected function doDelete($entities);

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $id = $entity->id();

    // Track the original ID.
    if ($entity->getOriginalId() !== NULL) {
      $id = $entity->getOriginalId();
    }

    // Track if this entity is new.
    $is_new = $entity->isNew();
    // Track if this entity exists already.
    $id_exists = $this->has($id, $entity);

    // A new entity should not already exist.
    if ($id_exists && $is_new) {
      throw new EntityStorageException(SafeMarkup::format('@type entity with ID @id already exists.', array('@type' => $this->entityTypeId, '@id' => $id)));
    }

    // Load the original entity, if any.
    if ($id_exists && !isset($entity->original)) {
      $entity->original = $this->loadUnchanged($id);
    }

    // Allow code to run before saving.
    $entity->preSave($this);
    $this->invokeHook('presave', $entity);

    // Perform the save and reset the static cache for the changed entity.
    $return = $this->doSave($id, $entity);
    $this->resetCache(array($id));

    // The entity is no longer new.
    $entity->enforceIsNew(FALSE);

    // Allow code to run after saving.
    $entity->postSave($this, !$is_new);
    $this->invokeHook($is_new ? 'insert' : 'update', $entity);

    // After saving, this is now the "original entity", and subsequent saves
    // will be updates instead of inserts, and updates must always be able to
    // correctly identify the original entity.
    $entity->setOriginalId($entity->id());

    unset($entity->original);

    return $return;
  }

  /**
   * Performs storage-specific saving of the entity.
   *
   * @param int|string $id
   *   The original entity ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   *
   * @return bool|int
   *   If the record insert or update failed, returns FALSE. If it succeeded,
   *   returns SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  abstract protected function doSave($id, EntityInterface $entity);

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
      // Cast scalars to array so we can consistently use an IN condition.
      $entity_query->condition($name, (array) $value, 'IN');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = array()) {
    // Build a query to fetch the entity IDs.
    $entity_query = $this->getQuery();
    $this->buildPropertyQuery($entity_query, $values);
    $result = $entity_query->execute();
    return $result ? $this->loadMultiple($result) : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($conjunction = 'AND') {
    // Access the service directly rather than entity.query factory so the
    // storage's current entity type is used.
    return \Drupal::service($this->getQueryServiceName())->get($this->entityType, $conjunction);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateQuery($conjunction = 'AND') {
    // Access the service directly rather than entity.query factory so the
    // storage's current entity type is used.
    return \Drupal::service($this->getQueryServiceName())->getAggregate($this->entityType, $conjunction);
  }

  /**
   * Gets the name of the service for the query for this entity storage.
   *
   * @return string
   *   The name of the service for the query for this entity storage.
   */
  abstract protected function getQueryServiceName();

}
