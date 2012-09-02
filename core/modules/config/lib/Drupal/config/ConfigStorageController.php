<?php

/**
 * @file
 * Definition of Drupal\config\ConfigStorageController.
 */

namespace Drupal\config;

use Drupal\Component\Uuid\Uuid;
use Drupal\entity\EntityInterface;
use Drupal\entity\StorageControllerInterface;

/**
 * Defines the storage controller class for configuration entities.
 */
class ConfigStorageController implements StorageControllerInterface {

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
   * Additional arguments to pass to hook_TYPE_load().
   *
   * Set before calling Drupal\config\ConfigStorageController::attachLoad().
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
   * Name of the entity's UUID property.
   *
   * @var string
   */
  protected $uuidKey = 'uuid';

  /**
   * Implements Drupal\entity\StorageControllerInterface::__construct().
   *
   * Sets basic variables.
   */
  public function __construct($entityType) {
    $this->entityType = $entityType;
    $this->entityInfo = entity_get_info($entityType);
    $this->hookLoadArguments = array();
    $this->idKey = $this->entityInfo['entity keys']['id'];
  }

  /**
   * Implements Drupal\entity\StorageControllerInterface::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    // The configuration system is fast enough and/or implements its own
    // (advanced) caching mechanism already.
  }

  /**
   * Implements Drupal\entity\StorageControllerInterface::load().
   */
  public function load(array $ids = NULL) {
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

    // Pass all entities loaded from the database through $this->attachLoad(),
    // which calls the
    // entity type specific load callback, for example hook_node_type_load().
    if (!empty($queried_entities)) {
      $this->attachLoad($queried_entities);
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
   * Implements Drupal\entity\StorageControllerInterface::loadRevision().
   */
  public function loadRevision($revision_id) {
    return FALSE;
  }

  /**
   * Implements Drupal\entity\StorageControllerInterface::loadByProperties().
   */
  public function loadByProperties(array $values = array()) {
    return array();
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
    $config_class = $this->entityInfo['entity class'];
    $prefix = $this->entityInfo['config prefix'] . '.';

    // Load all of the configuration entities.
    if ($ids === NULL) {
      $names = drupal_container()->get('config.storage')->listAll($prefix);
      $result = array();
      foreach ($names as $name) {
        $config = config($name);
        $result[$config->get($this->idKey)] = new $config_class($config->get(), $this->entityType);
      }
      return $result;
    }
    else {
      $result = array();
      foreach ($ids as $id) {
        // Add the prefix to the ID to serve as the configuration object name.
        $config = config($prefix . $id);
        if (!$config->isNew()) {
          $result[$id] = new $config_class($config->get(), $this->entityType);
        }
      }
      return $result;
    }
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
   * @param $revision_id
   *   ID of the revision that was loaded, or FALSE if the most current revision
   *   was loaded.
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
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
   * Implements Drupal\entity\StorageControllerInterface::create().
   */
  public function create(array $values) {
    $class = isset($this->entityInfo['entity class']) ? $this->entityInfo['entity class'] : 'Drupal\entity\Entity';

    $entity = new $class($values, $this->entityType);

    // Assign a new UUID if there is none yet.
    if (!isset($entity->{$this->uuidKey})) {
      $uuid = new Uuid();
      $entity->{$this->uuidKey} = $uuid->generate();
    }

    return $entity;
  }

  /**
   * Implements Drupal\entity\StorageControllerInterface::delete().
   */
  public function delete($ids) {
    $entities = $ids ? $this->load($ids) : FALSE;
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }

    $this->preDelete($entities);
    foreach ($entities as $id => $entity) {
      $this->invokeHook('predelete', $entity);
    }

    foreach ($entities as $id => $entity) {
      $config = config($this->entityInfo['config prefix'] . '.' . $entity->id());
      $config->delete();
    }

    $this->postDelete($entities);
    foreach ($entities as $id => $entity) {
      $this->invokeHook('delete', $entity);
    }
  }

  /**
   * Implements Drupal\entity\StorageControllerInterface::save().
   */
  public function save(EntityInterface $entity) {
    $prefix = $this->entityInfo['config prefix'] . '.';

    // Load the stored entity, if any.
    if ($entity->getOriginalID()) {
      $id = $entity->getOriginalID();
    }
    else {
      $id = $entity->id();
    }
    $config = config($prefix . $id);
    $config->setName($prefix . $entity->id());

    if (!$config->isNew() && !isset($entity->original)) {
      $entity->original = entity_load_unchanged($this->entityType, $id);
    }

    $this->preSave($entity);
    $this->invokeHook('presave', $entity);

    // Configuration objects do not have a schema. Extract all key names from
    // class properties.
    $class_info = new \ReflectionClass($entity);
    foreach ($class_info->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();
      $config->set($name, $entity->$name);
    }

    if (!$config->isNew()) {
      $return = SAVED_NEW;
      $config->save();
      $this->postSave($entity, TRUE);
      $this->invokeHook('update', $entity);
    }
    else {
      $return = SAVED_UPDATED;
      $config->save();
      $entity->enforceIsNew(FALSE);
      $this->postSave($entity, FALSE);
      $this->invokeHook('insert', $entity);
    }

    unset($entity->original);

    return $return;
  }

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   */
  protected function preSave(EntityInterface $entity) {
  }

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
  protected function postSave(EntityInterface $entity, $update) {
    // Delete the original configuration entity, in case the entity ID was
    // renamed.
    if ($update && !empty($entity->original) && $entity->{$this->idKey} !== $entity->original->{$this->idKey}) {
      // @todo This should just delete the original config object without going
      //   through the API, no?
      $entity->original->delete();
    }
  }

  /**
   * Acts on entities before they are deleted.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   */
  protected function preDelete($entities) {
  }

  /**
   * Acts on deleted entities before the delete hook is invoked.
   *
   * Used after the entities are deleted but before invoking the delete hook.
   */
  protected function postDelete($entities) {
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
    module_invoke_all($this->entityType . '_' . $hook, $entity);
    // Invoke the respective entity-level hook.
    module_invoke_all('entity_' . $hook, $entity, $this->entityType);
  }
}
