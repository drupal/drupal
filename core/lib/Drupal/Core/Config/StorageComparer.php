<?php

/**
 * @file
 * Contains \Drupal\Core\Config\StorageComparer.
 */

namespace Drupal\Core\Config;
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
   *   Storage controller object used to read configuration.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   Storage controller object used to write configuration.
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
   *   The change operation performed. Either delete, create or update.
   * @param array $changes
   *   Array of changes to add to the changelist.
   */
  protected function addChangeList($op, array $changes) {
    // Only add changes that aren't already listed.
    $changes = array_diff($changes, $this->changelist[$op]);
    $this->changelist[$op] = array_merge($this->changelist[$op], $changes);
  }

  /**
   * {@inheritdoc}
   */
  public function createChangelist() {
    $this->getAndSortConfigData();
    $this->addChangelistCreate();
    $this->addChangelistUpdate();
    $this->addChangelistDelete();
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
      $this->addChangeList('create', $recreates);
      $this->addChangeList('delete', array_reverse($recreates));
    }
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
  public function hasChanges($ops = array('delete', 'create', 'update')) {
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

}
