<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigStorageController.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageControllerBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage controller class for configuration entities.
 *
 * Configuration object names of configuration entities are comprised of two
 * parts, separated by a dot:
 * - config_prefix: A string denoting the owner (module/extension) of the
 *   configuration object, followed by arbitrary other namespace identifiers
 *   that are declared by the owning extension; e.g., 'node.type'. The
 *   config_prefix does NOT contain a trailing dot. It is defined by the entity
 *   type's annotation.
 * - ID: A string denoting the entity ID within the entity type namespace; e.g.,
 *   'article'. Entity IDs may contain dots/periods. The entire remaining string
 *   after the config_prefix in a config name forms the entity ID. Additional or
 *   custom suffixes are not possible.
 */
class ConfigStorageController extends EntityStorageControllerBase implements ConfigStorageControllerInterface {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Name of the entity's status key or FALSE if a status is not supported.
   *
   * @var string|bool
   */
  protected $statusKey = 'status';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Constructs a ConfigStorageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, StorageInterface $config_storage, UuidInterface $uuid_service) {
    parent::__construct($entity_type);

    $this->idKey = $this->entityType->getKey('id');
    $this->statusKey = $this->entityType->getKey('status');

    $this->configFactory = $config_factory;
    $this->configStorage = $config_storage;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('config.storage'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $entities = array();

    // Create a new variable which is either a prepared version of the $ids
    // array for later comparison with the entity cache, or FALSE if no $ids
    // were passed.
    $passed_ids = !empty($ids) ? array_flip($ids) : FALSE;

    // Load any remaining entities. This is the case if $ids is set to NULL (so
    // we load all entities).
    if ($ids === NULL || $ids) {
      $queried_entities = $this->buildQuery($ids);
    }

    // Pass all entities loaded from the database through $this->postLoad(),
    // which calls the
    // entity type specific load callback, for example hook_node_type_load().
    if (!empty($queried_entities)) {
      $this->postLoad($queried_entities);
      $entities += $queried_entities;
    }

    // Ensure that the returned array is ordered the same as the original
    // $ids array if this was passed in and remove any invalid ids.
    if ($passed_ids) {
      // Remove any invalid ids from the array.
      $passed_ids = array_intersect_key($passed_ids, $entities);
      foreach ($entities as $entity) {
        $passed_ids[$entity->{$this->idKey}] = $entity;
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
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::loadRevision().
   */
  public function loadRevision($revision_id) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::deleteRevision().
   */
  public function deleteRevision($revision_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    return $this->entityType->getConfigPrefix() . '.';
  }

  /**
   * {@inheritdoc}
   */
  public static function getIDFromConfigName($config_name, $config_prefix) {
    return substr($config_name, strlen($config_prefix . '.'));
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
   * See Drupal\comment\CommentStorageController::buildQuery() or
   * Drupal\taxonomy\TermStorageController::buildQuery() for examples.
   *
   * @param $ids
   *   An array of entity IDs, or NULL to load all entities.
   * @param $revision_id
   *   The ID of the revision to load, or FALSE if this query is asking for the
   *   most current revision(s).
   *
   * @return SelectQuery
   *   A SelectQuery object for loading the entity.
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $config_class = $this->entityType->getClass();
    $prefix = $this->getConfigPrefix();

    // Get the names of the configuration entities we are going to load.
    if ($ids === NULL) {
      $names = $this->configStorage->listAll($prefix);
    }
    else {
      $names = array();
      foreach ($ids as $id) {
        // Add the prefix to the ID to serve as the configuration object name.
        $names[] = $prefix . $id;
      }
    }

    // Load all of the configuration entities.
    $result = array();
    foreach ($this->configFactory->loadMultiple($names) as $config) {
      $result[$config->get($this->idKey)] = new $config_class($config->get(), $this->entityTypeId);
    }
    return $result;
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::create().
   */
  public function create(array $values = array()) {
    $class = $this->entityType->getClass();
    $class::preCreate($this, $values);

    // Set default language to site default if not provided.
    $values += array('langcode' => language_default()->id);

    $entity = new $class($values, $this->entityTypeId);
    // Mark this entity as new, so isNew() returns TRUE. This does not check
    // whether a configuration entity with the same ID (if any) already exists.
    $entity->enforceIsNew();

    // Assign a new UUID if there is none yet.
    if (!$entity->uuid()) {
      $entity->set('uuid', $this->uuidService->generate());
    }
    $entity->postCreate($this);

    // Modules might need to add or change the data initially held by the new
    // entity object, for instance to fill-in default values.
    $this->invokeHook('create', $entity);

    // Default status to enabled.
    if (!empty($this->statusKey) && !isset($entity->{$this->statusKey})) {
      $entity->{$this->statusKey} = TRUE;
    }

    return $entity;
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::delete().
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }

    $entity_class = $this->entityType->getClass();
    $entity_class::preDelete($this, $entities);
    foreach ($entities as $entity) {
      $this->invokeHook('predelete', $entity);
    }

    foreach ($entities as $entity) {
      $config = $this->configFactory->get($this->getConfigPrefix() . $entity->id());
      $config->delete();
    }

    $entity_class::postDelete($this, $entities);
    foreach ($entities as $entity) {
      $this->invokeHook('delete', $entity);
    }
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::save().
   *
   * @throws EntityMalformedException
   *   When attempting to save a configuration entity that has no ID.
   */
  public function save(EntityInterface $entity) {
    $prefix = $this->getConfigPrefix();

    // Configuration entity IDs are strings, and '0' is a valid ID.
    $id = $entity->id();
    if ($id === NULL || $id === '') {
      throw new EntityMalformedException('The entity does not have an ID.');
    }

    // Load the stored entity, if any.
    // At this point, the original ID can only be NULL or a valid ID.
    if ($entity->getOriginalId() !== NULL) {
      $id = $entity->getOriginalId();
    }
    $config = $this->configFactory->get($prefix . $id);

    // Prevent overwriting an existing configuration file if the entity is new.
    if ($entity->isNew() && !$config->isNew()) {
      throw new EntityStorageException(String::format('@type entity with ID @id already exists.', array('@type' => $this->entityTypeId, '@id' => $id)));
    }

    if (!$config->isNew() && !isset($entity->original)) {
      $this->resetCache(array($id));
      $entity->original = $this->load($id);
    }

    if ($id !== $entity->id()) {
      // Renaming a config object needs to cater for:
      // - Storage controller needs to access the original object.
      // - The object needs to be renamed/copied in ConfigFactory and reloaded.
      // - All instances of the object need to be renamed.
      $config = $this->configFactory->rename($prefix . $id, $prefix . $entity->id());
    }

    // Build an ID if none is set.
    if (!isset($entity->{$this->idKey})) {
      $entity->{$this->idKey} = $entity->id();
    }

    $entity->preSave($this);
    $this->invokeHook('presave', $entity);

    // Retrieve the desired properties and set them in config.
    foreach ($entity->getExportProperties() as $key => $value) {
      $config->set($key, $value);
    }

    if (!$config->isNew()) {
      $return = SAVED_UPDATED;
      $config->save();
      $entity->postSave($this, TRUE);
      $this->invokeHook('update', $entity);

      // Immediately update the original ID.
      $entity->setOriginalId($entity->id());
    }
    else {
      $return = SAVED_NEW;
      $config->save();
      $entity->enforceIsNew(FALSE);
      $entity->postSave($this, FALSE);
      $this->invokeHook('insert', $entity);
    }

    unset($entity->original);

    return $return;
  }

  /**
   * Invokes a hook on behalf of the entity.
   *
   * @param $hook
   *   One of 'presave', 'insert', 'update', 'predelete', or 'delete'.
   * @param $entity
   *   The entity object.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    // Invoke the hook.
    $this->moduleHandler->invokeAll($this->entityTypeId . '_' . $hook, array($entity));
    // Invoke the respective entity-level hook.
    $this->moduleHandler->invokeAll('entity_' . $hook, array($entity, $this->entityTypeId));
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::getQueryServicename().
   */
  public function getQueryServicename() {
    return 'entity.query.config';
  }

  /**
   * {@inheritdoc}
   */
  public function importCreate($name, Config $new_config, Config $old_config) {
    $entity = $this->create($new_config->get());
    $entity->setSyncing(TRUE);
    $entity->save();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function importUpdate($name, Config $new_config, Config $old_config) {
    $id = static::getIDFromConfigName($name, $this->entityType->getConfigPrefix());
    $entity = $this->load($id);
    $entity->setSyncing(TRUE);
    $entity->original = clone $entity;

    foreach ($old_config->get() as $property => $value) {
      $entity->original->set($property, $value);
    }

    foreach ($new_config->get() as $property => $value) {
      $entity->set($property, $value);
    }

    $entity->save();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    $id = static::getIDFromConfigName($name, $this->entityType->getConfigPrefix());
    $entity = $this->load($id);
    $entity->setSyncing(TRUE);
    $entity->delete();
    return TRUE;
  }

}
