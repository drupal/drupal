<?php

/**
 * @file
 * Contains \Drupal\Core\Config\StorageComparer.
 */

namespace Drupal\Core\Config;

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
   * Lists all the configuration object names in the source storage.
   *
   * @see \Drupal\Core\Config\StorageComparer::getSourceNames()
   *
   * @var array
   */
  protected $sourceNames = array();

  /**
   * Lists all the configuration object names in the target storage.
   *
   * @see \Drupal\Core\Config\StorageComparer::getTargetNames()
   *
   * @var array
   */
  protected $targetNames = array();

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
   * {@inheritdoc}
   */
  public function addChangeList($op, array $changes) {
    // Only add changes that aren't already listed.
    $changes = array_diff($changes, $this->changelist[$op]);
    $this->changelist[$op] = array_merge($this->changelist[$op], $changes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createChangelist() {
    return $this
      ->addChangelistCreate()
      ->addChangelistUpdate()
      ->addChangelistDelete();
  }

  /**
   * {@inheritdoc}
   */
  public function addChangelistDelete() {
    return $this->addChangeList('delete', array_diff($this->getTargetNames(), $this->getSourceNames()));
  }

  /**
   * {@inheritdoc}
   */
  public function addChangelistCreate() {
    return $this->addChangeList('create', array_diff($this->getSourceNames(), $this->getTargetNames()));
  }

  /**
   * {@inheritdoc}
   */
  public function addChangelistUpdate() {
    foreach (array_intersect($this->getSourceNames(), $this->getTargetNames()) as $name) {
      $source_config_data = $this->sourceStorage->read($name);
      $target_config_data = $this->targetStorage->read($name);
      if ($source_config_data !== $target_config_data) {
        $this->addChangeList('update', array($name));
      }
    }
    return $this;
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
   * Gets all the configuration names in the source storage.
   *
   * @return array
   *   List of all the configuration names in the source storage.
   */
  protected function getSourceNames() {
    if (empty($this->sourceNames)) {
      $this->sourceNames = $this->sourceStorage->listAll();
    }
    return $this->sourceNames;
  }

  /**
   * Gets all the configuration names in the target storage.
   *
   * @return array
   *   List of all the configuration names in the target storage.
   */
  protected function getTargetNames() {
    if (empty($this->targetNames)) {
      $this->targetNames = $this->targetStorage->listAll();
    }
    return $this->targetNames;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSiteUuid() {
    $source = $this->sourceStorage->read('system.site');
    $target = $this->targetStorage->read('system.site');
    return $source['uuid'] === $target['uuid'];
  }

}
