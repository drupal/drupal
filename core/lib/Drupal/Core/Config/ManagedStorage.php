<?php

namespace Drupal\Core\Config;

/**
 * The managed storage defers all the storage method calls to the manager.
 *
 * The reason for deferring all the method calls is that the storage interface
 * is the API but we potentially need to do an expensive transformation before
 * the storage can be used so we can't do it in the constructor but we also
 * don't know which method is called first.
 *
 * This class is not meant to be extended and is final to make sure the
 * assumptions that the storage is retrieved only once are upheld.
 */
final class ManagedStorage implements StorageInterface {

  /**
   * The decorated storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The storage manager to get the storage to decorate.
   *
   * @var \Drupal\Core\Config\StorageManagerInterface
   */
  protected $manager;

  /**
   * ManagedStorage constructor.
   *
   * @param \Drupal\Core\Config\StorageManagerInterface $manager
   *   The storage manager.
   */
  public function __construct(StorageManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return $this->getStorage()->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    return $this->getStorage()->read($name);
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    return $this->getStorage()->readMultiple($names);
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    return $this->getStorage()->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    return $this->getStorage()->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    return $this->getStorage()->rename($name, $new_name);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $this->getStorage()->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $this->getStorage()->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    return $this->getStorage()->listAll($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    return $this->getStorage()->deleteAll($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    // We return the collection directly.
    // This means that the collection will not be an instance of ManagedStorage
    // But this doesn't matter because the storage is retrieved from the
    // manager only the first time it is accessed.
    return $this->getStorage()->createCollection($collection);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    return $this->getStorage()->getAllCollectionNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->getStorage()->getCollectionName();
  }

  /**
   * Get the decorated storage from the manager if necessary.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  protected function getStorage() {
    // Get the storage from the manager the first time it is needed.
    if (!isset($this->storage)) {
      $this->storage = $this->manager->getStorage();
    }

    return $this->storage;
  }

}
