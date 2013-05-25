<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigStorageController.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageInterface;
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
class ConfigStorageController implements EntityStorageControllerInterface, EntityControllerInterface {

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
   * Set before calling Drupal\Core\Config\Entity\ConfigStorageController::attachLoad().
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
   * Name of the entity's status key or FALSE if a status is not supported.
   *
   * @var string|bool
   */
  protected $statusKey = 'status';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
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
   * @param string $entity_type
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage service.
   */
  public function __construct($entity_type, array $entity_info, ConfigFactory $config_factory, StorageInterface $config_storage) {
    $this->entityType = $entity_type;
    $this->entityInfo = $entity_info;
    $this->hookLoadArguments = array();
    $this->idKey = $this->entityInfo['entity_keys']['id'];

    if (isset($this->entityInfo['entity_keys']['status'])) {
      $this->statusKey = $this->entityInfo['entity_keys']['status'];
    }
    else {
      $this->statusKey = FALSE;
    }

    $this->configFactory = $config_factory;
    $this->configStorage = $config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('config.factory'),
      $container->get('config.storage')
    );
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    // The configuration system is fast enough and/or implements its own
    // (advanced) caching mechanism already.
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::load().
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
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::loadUnchanged()
   */
  public function loadUnchanged($id) {
    $this->resetCache(array($id));
    $result = $this->load(array($id));
    return reset($result);
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
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::loadByProperties().
   */
  public function loadByProperties(array $values = array()) {
    $entities = $this->load();
    foreach ($values as $key => $value) {
      $entities = array_filter($entities, function($entity) use ($key, $value) {
        return $value === $entity->get($key);
      });
    }
    return $entities;
  }

  /**
   * Returns the config prefix used by the configuration entity type.
   *
   * @return string
   *   The full configuration prefix, for example 'views.view.'.
   */
  public function getConfigPrefix() {
    return $this->entityInfo['config_prefix'] . '.';
  }

  /**
   * Extracts the configuration entity ID from the full configuration name.
   *
   * @param string $config_name
   *   The full configuration name to extract the ID from. E.g.
   *   'views.view.archive'.
   * @param string $config_prefix
   *   The config prefix of the configuration entity. E.g. 'views.view'
   *
   * @return string
   *   The ID of the configuration entity.
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
    $config_class = $this->entityInfo['class'];
    $prefix = $this->getConfigPrefix();

    // Load all of the configuration entities.
    if ($ids === NULL) {
      $names = $this->configStorage->listAll($prefix);
      $result = array();
      foreach ($names as $name) {
        $config = $this->configFactory->get($name);
        $result[$config->get($this->idKey)] = new $config_class($config->get(), $this->entityType);
      }
      return $result;
    }
    else {
      $result = array();
      foreach ($ids as $id) {
        // Add the prefix to the ID to serve as the configuration object name.
        $config = $this->configFactory->get($prefix . $id);
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
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::create().
   */
  public function create(array $values) {
    $class = $this->entityInfo['class'];

    // Set default language to site default if not provided.
    $values += array('langcode' => language_default()->langcode);

    $entity = new $class($values, $this->entityType);
    // Mark this entity as new, so isNew() returns TRUE. This does not check
    // whether a configuration entity with the same ID (if any) already exists.
    $entity->enforceIsNew();

    // Assign a new UUID if there is none yet.
    if (!isset($entity->{$this->uuidKey})) {
      $uuid = new Uuid();
      $entity->{$this->uuidKey} = $uuid->generate();
    }

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

    $this->preDelete($entities);
    foreach ($entities as $id => $entity) {
      $this->invokeHook('predelete', $entity);
    }

    foreach ($entities as $id => $entity) {
      $config = $this->configFactory->get($this->getConfigPrefix() . $entity->id());
      $config->delete();

      // Remove the entity from the manifest file. Entity IDs can contain a dot
      // so we can not use Config::clear() to remove the entity from the
      // manifest.
      $manifest = $this->configFactory->get('manifest.' . $this->entityInfo['config_prefix']);
      $manifest_data = $manifest->get();
      unset($manifest_data[$entity->id()]);
      $manifest->setData($manifest_data);
      $manifest->save();
    }

    $this->postDelete($entities);
    foreach ($entities as $id => $entity) {
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
    if ($entity->getOriginalID() !== NULL) {
      $id = $entity->getOriginalID();
    }
    $config = $this->configFactory->get($prefix . $id);
    $is_new = $config->isNew();

    if (!$is_new && !isset($entity->original)) {
      $this->resetCache(array($id));
      $result = $this->load(array($id));
      $entity->original = reset($result);
    }

    if ($id !== $entity->id()) {
      // Renaming a config object needs to cater for:
      // - Storage controller needs to access the original object.
      // - The object needs to be renamed/copied in ConfigFactory and reloaded.
      // - All instances of the object need to be renamed.
      $this->configFactory->rename($prefix . $id, $prefix . $entity->id());
    }

    $this->preSave($entity);
    $this->invokeHook('presave', $entity);

    // Retrieve the desired properties and set them in config.
    foreach ($entity->getExportProperties() as $key => $value) {
      $config->set($key, $value);
    }

    if (!$is_new) {
      $return = SAVED_UPDATED;
      $config->save();
      $this->postSave($entity, TRUE);
      $this->invokeHook('update', $entity);

      // Immediately update the original ID.
      $entity->setOriginalID($entity->id());
    }
    else {
      $return = SAVED_NEW;
      $config->save();
      $entity->enforceIsNew(FALSE);
      $this->postSave($entity, FALSE);
      $this->invokeHook('insert', $entity);
    }

    $update_manifest = FALSE;
    $config = $this->configFactory->get('manifest.' . $this->entityInfo['config_prefix']);
    $manifest = $config->get();
    // If the save operation resulted in a rename remove the old entity id from
    // the manifest file.
    if ($id !== $entity->id()) {
      // Entity IDs can contain a dot so we can not use Config::clear() to
      // remove the entity from the manifest.
      unset($manifest[$id]);
      $update_manifest = TRUE;
    }
    // Add this entity to the manifest file if necessary.
    if (!isset($manifest[$entity->id()])) {
      $manifest[$entity->id()] = array(
        'name' => $this->getConfigPrefix() . $entity->id(),
      );
      $update_manifest = TRUE;
    }
    if ($update_manifest) {
      $config->setData($manifest)->save();
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
   * @param EntityInterface $entity
   *   The entity to act on.
   * @param $update
   *   (bool) TRUE if the entity has been updated, or FALSE if it has been
   *   inserted.
   */
  protected function postSave(EntityInterface $entity, $update) {
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
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::getFieldDefinitions().
   */
  public function getFieldDefinitions(array $constraints) {
    return array();
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

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::getQueryServicename().
   */
  public function getQueryServicename() {
    return 'entity.query.config';
  }

  /**
   * Create configuration upon synchronizing configuration changes.
   *
   * This callback is invoked when configuration is synchronized between storages
   * and allows a module to take over the synchronization of configuration data.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param \Drupal\Core\Config\Config $new_config
   *   A configuration object containing the new configuration data.
   * @param \Drupal\Core\Config\Config $old_config
   *   A configuration object containing the old configuration data.
   */
  public function importCreate($name, Config $new_config, Config $old_config) {
    $entity = $this->create($new_config->get());
    $entity->save();
    return TRUE;
  }

  /**
   * Updates configuration upon synchronizing configuration changes.
   *
   * This callback is invoked when configuration is synchronized between storages
   * and allows a module to take over the synchronization of configuration data.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param \Drupal\Core\Config\Config $new_config
   *   A configuration object containing the new configuration data.
   * @param \Drupal\Core\Config\Config $old_config
   *   A configuration object containing the old configuration data.
   */
  public function importUpdate($name, Config $new_config, Config $old_config) {
    $id = static::getIDFromConfigName($name, $this->entityInfo['config_prefix']);
    $entities = $this->load(array($id));
    $entity = $entities[$id];
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
   * Delete configuration upon synchronizing configuration changes.
   *
   * This callback is invoked when configuration is synchronized between storages
   * and allows a module to take over the synchronization of configuration data.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param \Drupal\Core\Config\Config $new_config
   *   A configuration object containing the new configuration data.
   * @param \Drupal\Core\Config\Config $old_config
   *   A configuration object containing the old configuration data.
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    $id = static::getIDFromConfigName($name, $this->entityInfo['config_prefix']);
    $entities = $this->load(array($id));
    $entity = $entities[$id];
    $entity->delete();
    return TRUE;
  }

}
