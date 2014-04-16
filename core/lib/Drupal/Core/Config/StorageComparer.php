<?php

/**
 * @file
 * Contains \Drupal\Core\Config\StorageComparer.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ConfigDependencyManager;

/**
 * Defines a config storage comparer.
 */
class StorageComparer implements StorageComparerInterface {

  /**
   * The source storage used to discover configuration changes.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * The target storage used to write configuration changes.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * List of changes to between the source storage and the target storage.
   *
   * @var array
   */
  protected $changelist;

  /**
   * Sorted list of all the configuration object names in the source storage.
   *
   * @var array
   */
  protected $sourceNames = array();

  /**
   * Sorted list of all the configuration object names in the target storage.
   *
   * @var array
   */
  protected $targetNames = array();

  /**
   * The source configuration data keyed by name.
   *
   * @var array
   */
  protected $sourceData = array();

  /**
   * The target configuration data keyed by name.
   *
   * @var array
   */
  protected $targetData = array();

  /**
   * Constructs the Configuration storage comparer.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   Storage object used to read configuration.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   Storage object used to write configuration.
   */
  public function __construct(StorageInterface $source_storage, StorageInterface $target_storage) {
    $this->sourceStorage = $source_storage;
    $this->targetStorage = $target_storage;
    $this->changelist = $this->getEmptyChangelist();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceStorage() {
    return $this->sourceStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetStorage() {
    return $this->targetStorage;
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
  public function getChangelist($op = NULL) {
    if ($op) {
      return $this->changelist[$op];
    }
    return $this->changelist;
  }

  /**
   * Adds changes to the changelist.
   *
   * @param string $op
   *   The change operation performed. Either delete, create, rename, or update.
   * @param array $changes
   *   Array of changes to add to the changelist.
   * @param array $sort_order
   *   Array to sort that can be used to sort the changelist. This array must
   *   contain all the items that are in the change list.
   */
  protected function addChangeList($op, array $changes, array $sort_order = NULL) {
    // Only add changes that aren't already listed.
    $changes = array_diff($changes, $this->changelist[$op]);
    $this->changelist[$op] = array_merge($this->changelist[$op], $changes);
    if (isset($sort_order)) {
      $count = count($this->changelist[$op]);
      // Sort the changlist in the same order as the $sort_order array and
      // ensure the array is keyed from 0.
      $this->changelist[$op] = array_values(array_intersect($sort_order, $this->changelist[$op]));
      if ($count != count($this->changelist[$op])) {
        throw new \InvalidArgumentException(String::format('Sorting the @op changelist should not change its length.', array('@op' => $op)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createChangelist() {
    $this->getAndSortConfigData();
    $this->addChangelistCreate();
    $this->addChangelistUpdate();
    $this->addChangelistDelete();
    $this->addChangelistRename();
    $this->sourceData = NULL;
    $this->targetData = NULL;
    return $this;
  }

  /**
   * Creates the delete changelist.
   *
   * The list of deletes is sorted so that dependencies are deleted after
   * configuration entities that depend on them. For example, field instances
   * should be deleted after fields.
   */
  protected function addChangelistDelete() {
    $deletes = array_diff(array_reverse($this->targetNames), $this->sourceNames);
    $this->addChangeList('delete', $deletes);
  }

  /**
   * Creates the create changelist.
   *
   * The list of creates is sorted so that dependencies are created before
   * configuration entities that depend on them. For example, fields
   * should be created before field instances.
   */
  protected function addChangelistCreate() {
    $creates = array_diff($this->sourceNames, $this->targetNames);
    $this->addChangeList('create', $creates);
  }

  /**
   * Creates the update changelist.
   *
   * The list of updates is sorted so that dependencies are created before
   * configuration entities that depend on them. For example, fields
   * should be updated before field instances.
   */
  protected function addChangelistUpdate() {
    $recreates = array();
    foreach (array_intersect($this->sourceNames, $this->targetNames) as $name) {
      if ($this->sourceData[$name] !== $this->targetData[$name]) {
        if (isset($this->sourceData[$name]['uuid']) && $this->sourceData[$name]['uuid'] != $this->targetData[$name]['uuid']) {
          // The entity has the same file as an existing entity but the UUIDs do
          // not match. This means that the entity has been recreated so config
          // synchronisation should do the same.
          $recreates[] = $name;
        }
        else {
          $this->addChangeList('update', array($name));
        }
      }
    }

    if (!empty($recreates)) {
      // Recreates should become deletes and creates. Deletes should be ordered
      // so that dependencies are deleted first.
      $this->addChangeList('create', $recreates, $this->sourceNames);
      $this->addChangeList('delete', $recreates, array_reverse($this->targetNames));

    }
  }

  /**
   * Creates the rename changelist.
   *
   * The list of renames is created from the different source and target names
   * with same UUID. These changes will be removed from the create and delete
   * lists.
   */
  protected function addChangelistRename() {
    // Renames will be present in both the create and delete lists.
    $create_list = $this->getChangelist('create');
    $delete_list = $this->getChangelist('delete');
    if (empty($create_list) || empty($delete_list)) {
      return;
    }

    $create_uuids = array();
    foreach ($this->sourceData as $id => $data) {
      if (isset($data['uuid']) && in_array($id, $create_list)) {
        $create_uuids[$data['uuid']] = $id;
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
    foreach ($this->targetNames as $name) {
      $data = $this->targetData[$name];
      if (isset($data['uuid']) && isset($create_uuids[$data['uuid']])) {
        // Remove the item from the create list.
        $this->removeFromChangelist('create', $create_uuids[$data['uuid']]);
        // Remove the item from the delete list.
        $this->removeFromChangelist('delete', $name);
        // Create the rename name.
        $renames[] = $this->createRenameName($name, $create_uuids[$data['uuid']]);
      }
    }

    $this->addChangeList('rename', $renames);
  }

  /**
   * Removes the entry from the given operation changelist for the given name.
   *
   * @param string $op
   *   The changelist to act on. Either delete, create, rename or update.
   * @param string $name
   *   The name of the configuration to remove.
   */
  protected function removeFromChangelist($op, $name) {
    $key = array_search($name, $this->changelist[$op]);
    if ($key !== FALSE) {
      unset($this->changelist[$op][$key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function moveRenameToUpdate($rename) {
    $names = $this->extractRenameNames($rename);
    $this->removeFromChangelist('rename', $rename);
    $this->addChangeList('update', array($names['new_name']), $this->sourceNames);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->changelist = $this->getEmptyChangelist();
    $this->sourceNames = $this->targetNames = array();
    return $this->createChangelist();
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanges($ops = array('delete', 'create', 'update', 'rename')) {
    foreach ($ops as $op) {
      if (!empty($this->changelist[$op])) {
        return TRUE;
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
  protected function getAndSortConfigData() {
    $this->targetData = $this->targetStorage->readMultiple($this->targetStorage->listAll());
    $this->sourceData = $this->sourceStorage->readMultiple($this->sourceStorage->listAll());
    $dependency_manager = new ConfigDependencyManager();
    $this->targetNames = $dependency_manager->setData($this->targetData)->sortAll();
    $this->sourceNames = $dependency_manager->setData($this->sourceData)->sortAll();
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
}
