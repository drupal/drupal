<?php

/**
 * @file
 * Contains \Drupal\Core\Config\StorageComparer.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Defines a config storage comparer.
 */
class StorageComparer implements StorageComparerInterface {
  use DependencySerializationTrait;

  /**
   * The source storage used to discover configuration changes.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * The source storages keyed by collection.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $sourceStorages;

  /**
   * The target storage used to write configuration changes.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The target storages keyed by collection.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $targetStorages;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * List of changes to between the source storage and the target storage.
   *
   * The list is keyed by storage collection name.
   *
   * @var array
   */
  protected $changelist;

  /**
   * Sorted list of all the configuration object names in the source storage.
   *
   * The list is keyed by storage collection name.
   *
   * @var array
   */
  protected $sourceNames = array();

  /**
   * Sorted list of all the configuration object names in the target storage.
   *
   * The list is keyed by storage collection name.
   *
   * @var array
   */
  protected $targetNames = array();

  /**
   * A memory cache backend to statically cache source configuration data.
   *
   * @var \Drupal\Core\Cache\MemoryBackend
   */
  protected $sourceCacheStorage;

  /**
   * A memory cache backend to statically cache target configuration data.
   *
   * @var \Drupal\Core\Cache\MemoryBackend
   */
  protected $targetCacheStorage;

  /**
   * Constructs the Configuration storage comparer.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   Storage object used to read configuration.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   Storage object used to write configuration.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(StorageInterface $source_storage, StorageInterface $target_storage, ConfigManagerInterface $config_manager) {
    // Wrap the storages in a static cache so that multiple reads of the same
    // raw configuration object are not costly.
    $this->sourceCacheStorage = new MemoryBackend(__CLASS__ . '::source');
    $this->sourceStorage = new CachedStorage(
      $source_storage,
      $this->sourceCacheStorage
    );
    $this->targetCacheStorage = new MemoryBackend(__CLASS__ . '::target');
    $this->targetStorage = new CachedStorage(
      $target_storage,
      $this->targetCacheStorage
    );
    $this->configManager = $config_manager;
    $this->changelist[StorageInterface::DEFAULT_COLLECTION] = $this->getEmptyChangelist();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceStorage($collection = StorageInterface::DEFAULT_COLLECTION) {
    if (!isset($this->sourceStorages[$collection])) {
      if ($collection == StorageInterface::DEFAULT_COLLECTION) {
        $this->sourceStorages[$collection] = $this->sourceStorage;
      }
      else {
        $this->sourceStorages[$collection] = $this->sourceStorage->createCollection($collection);
      }
    }
    return $this->sourceStorages[$collection];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetStorage($collection = StorageInterface::DEFAULT_COLLECTION) {
    if (!isset($this->targetStorages[$collection])) {
      if ($collection == StorageInterface::DEFAULT_COLLECTION) {
        $this->targetStorages[$collection] = $this->targetStorage;
      }
      else {
        $this->targetStorages[$collection] = $this->targetStorage->createCollection($collection);
      }
    }
    return $this->targetStorages[$collection];
  }

  /**
   * {@inheritdoc}
   */
  public function getEmptyChangelist() {
    return array(
      'create' => array(),
      'update' => array(),
      'delete' => array(),
      'rename' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getChangelist($op = NULL, $collection = StorageInterface::DEFAULT_COLLECTION) {
    if ($op) {
      return $this->changelist[$collection][$op];
    }
    return $this->changelist[$collection];
  }

  /**
   * Adds changes to the changelist.
   *
   * @param string $collection
   *   The storage collection to add changes for.
   * @param string $op
   *   The change operation performed. Either delete, create, rename, or update.
   * @param array $changes
   *   Array of changes to add to the changelist.
   * @param array $sort_order
   *   Array to sort that can be used to sort the changelist. This array must
   *   contain all the items that are in the change list.
   */
  protected function addChangeList($collection, $op, array $changes, array $sort_order = NULL) {
    // Only add changes that aren't already listed.
    $changes = array_diff($changes, $this->changelist[$collection][$op]);
    $this->changelist[$collection][$op] = array_merge($this->changelist[$collection][$op], $changes);
    if (isset($sort_order)) {
      $count = count($this->changelist[$collection][$op]);
      // Sort the changelist in the same order as the $sort_order array and
      // ensure the array is keyed from 0.
      $this->changelist[$collection][$op] = array_values(array_intersect($sort_order, $this->changelist[$collection][$op]));
      if ($count != count($this->changelist[$collection][$op])) {
        throw new \InvalidArgumentException(SafeMarkup::format('Sorting the @op changelist should not change its length.', array('@op' => $op)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createChangelist() {
    foreach ($this->getAllCollectionNames() as $collection) {
      $this->changelist[$collection] = $this->getEmptyChangelist();
      $this->getAndSortConfigData($collection);
      $this->addChangelistCreate($collection);
      $this->addChangelistUpdate($collection);
      $this->addChangelistDelete($collection);
      // Only collections that support configuration entities can have renames.
      if ($collection == StorageInterface::DEFAULT_COLLECTION) {
        $this->addChangelistRename($collection);
      }
    }
    return $this;
  }

  /**
   * Creates the delete changelist.
   *
   * The list of deletes is sorted so that dependencies are deleted after
   * configuration entities that depend on them. For example, fields should be
   * deleted after field storages.
   *
   * @param string $collection
   *   The storage collection to operate on.
   */
  protected function addChangelistDelete($collection) {
    $deletes = array_diff(array_reverse($this->targetNames[$collection]), $this->sourceNames[$collection]);
    $this->addChangeList($collection, 'delete', $deletes);
  }

  /**
   * Creates the create changelist.
   *
   * The list of creates is sorted so that dependencies are created before
   * configuration entities that depend on them. For example, field storages
   * should be created before fields.
   *
   * @param string $collection
   *   The storage collection to operate on.
   */
  protected function addChangelistCreate($collection) {
    $creates = array_diff($this->sourceNames[$collection], $this->targetNames[$collection]);
    $this->addChangeList($collection, 'create', $creates);
  }

  /**
   * Creates the update changelist.
   *
   * The list of updates is sorted so that dependencies are created before
   * configuration entities that depend on them. For example, field storages
   * should be updated before fields.
   *
   * @param string $collection
   *   The storage collection to operate on.
   */
  protected function addChangelistUpdate($collection) {
    $recreates = array();
    foreach (array_intersect($this->sourceNames[$collection], $this->targetNames[$collection]) as $name) {
      $source_data = $this->getSourceStorage($collection)->read($name);
      $target_data = $this->getTargetStorage($collection)->read($name);
      if ($source_data !== $target_data) {
        if (isset($source_data['uuid']) && $source_data['uuid'] !== $target_data['uuid']) {
          // The entity has the same file as an existing entity but the UUIDs do
          // not match. This means that the entity has been recreated so config
          // synchronization should do the same.
          $recreates[] = $name;
        }
        else {
          $this->addChangeList($collection, 'update', array($name));
        }
      }
    }

    if (!empty($recreates)) {
      // Recreates should become deletes and creates. Deletes should be ordered
      // so that dependencies are deleted first.
      $this->addChangeList($collection, 'create', $recreates, $this->sourceNames[$collection]);
      $this->addChangeList($collection, 'delete', $recreates, array_reverse($this->targetNames[$collection]));

    }
  }

  /**
   * Creates the rename changelist.
   *
   * The list of renames is created from the different source and target names
   * with same UUID. These changes will be removed from the create and delete
   * lists.
   *
   * @param string $collection
   *   The storage collection to operate on.
   */
  protected function addChangelistRename($collection) {
    // Renames will be present in both the create and delete lists.
    $create_list = $this->getChangelist('create', $collection);
    $delete_list = $this->getChangelist('delete', $collection);
    if (empty($create_list) || empty($delete_list)) {
      return;
    }

    $create_uuids = array();
    foreach ($this->sourceNames[$collection] as $name) {
      $data = $this->getSourceStorage($collection)->read($name);
      if (isset($data['uuid']) && in_array($name, $create_list)) {
        $create_uuids[$data['uuid']] = $name;
      }
    }
    if (empty($create_uuids)) {
      return;
    }

    $renames = array();

    // Renames should be ordered so that dependencies are renamed last. This
    // ensures that if there is logic in the configuration entity class to keep
    // names in sync it will still work. $this->targetNames is in the desired
    // order due to the use of configuration dependencies in
    // \Drupal\Core\Config\StorageComparer::getAndSortConfigData().
    // Node type is a good example of a configuration entity that renames other
    // configuration when it is renamed.
    // @see \Drupal\node\Entity\NodeType::postSave()
    foreach ($this->targetNames[$collection] as $name) {
      $data = $this->getTargetStorage($collection)->read($name);
      if (isset($data['uuid']) && isset($create_uuids[$data['uuid']])) {
        // Remove the item from the create list.
        $this->removeFromChangelist($collection, 'create', $create_uuids[$data['uuid']]);
        // Remove the item from the delete list.
        $this->removeFromChangelist($collection, 'delete', $name);
        // Create the rename name.
        $renames[] = $this->createRenameName($name, $create_uuids[$data['uuid']]);
      }
    }

    $this->addChangeList($collection, 'rename', $renames);
  }

  /**
   * Removes the entry from the given operation changelist for the given name.
   *
   * @param string $collection
   *   The storage collection to operate on.
   * @param string $op
   *   The changelist to act on. Either delete, create, rename or update.
   * @param string $name
   *   The name of the configuration to remove.
   */
  protected function removeFromChangelist($collection, $op, $name) {
    $key = array_search($name, $this->changelist[$collection][$op]);
    if ($key !== FALSE) {
      unset($this->changelist[$collection][$op][$key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function moveRenameToUpdate($rename, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $names = $this->extractRenameNames($rename);
    $this->removeFromChangelist($collection, 'rename', $rename);
    $this->addChangeList($collection, 'update', array($names['new_name']), $this->sourceNames[$collection]);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->changelist = array(StorageInterface::DEFAULT_COLLECTION => $this->getEmptyChangelist());
    $this->sourceNames = $this->targetNames = array();
    // Reset the static configuration data caches.
    $this->sourceCacheStorage->deleteAll();
    $this->targetCacheStorage->deleteAll();
    return $this->createChangelist();
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanges() {
    foreach ($this->getAllCollectionNames() as $collection) {
      foreach (array('delete', 'create', 'update', 'rename') as $op) {
        if (!empty($this->changelist[$collection][$op])) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSiteUuid() {
    $source = $this->sourceStorage->read('system.site');
    $target = $this->targetStorage->read('system.site');
    return $source['uuid'] === $target['uuid'];
  }

  /**
   * Gets and sorts configuration data from the source and target storages.
   */
  protected function getAndSortConfigData($collection) {
    $source_storage = $this->getSourceStorage($collection);
    $target_storage = $this->getTargetStorage($collection);
    $target_names = $target_storage->listAll();
    $source_names = $source_storage->listAll();
    // Prime the static caches by reading all the configuration in the source
    // and target storages.
    $target_data = $target_storage->readMultiple($target_names);
    $source_data = $source_storage->readMultiple($source_names);
    // If the collection only supports simple configuration do not use
    // configuration dependencies.
    if ($collection == StorageInterface::DEFAULT_COLLECTION) {
      $dependency_manager = new ConfigDependencyManager();
      $this->targetNames[$collection] = $dependency_manager->setData($target_data)->sortAll();
      $this->sourceNames[$collection] = $dependency_manager->setData($source_data)->sortAll();
    }
    else {
      $this->targetNames[$collection] = $target_names;
      $this->sourceNames[$collection] = $source_names;
    }
  }

  /**
   * Creates a rename name from the old and new names for the object.
   *
   * @param string $old_name
   *   The old configuration object name.
   * @param string $new_name
   *   The new configuration object name.
   *
   * @return string
   *   The configuration change name that encodes both the old and the new name.
   *
   * @see \Drupal\Core\Config\StorageComparerInterface::extractRenameNames()
   */
  protected function createRenameName($name1, $name2) {
    return $name1 . '::' . $name2;
  }

  /**
   * {@inheritdoc}
   */
  public function extractRenameNames($name) {
    $names = explode('::', $name, 2);
    return array(
      'old_name' => $names[0],
      'new_name' => $names[1],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames($include_default = TRUE) {
    $collections = array_unique(array_merge($this->sourceStorage->getAllCollectionNames(), $this->targetStorage->getAllCollectionNames()));
    if ($include_default) {
      array_unshift($collections, StorageInterface::DEFAULT_COLLECTION);
    }
    return $collections;
  }

}
