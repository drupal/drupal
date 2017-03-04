<?php

namespace Drupal\Core\Config;

/**
 * Defines a stub storage.
 *
 * This storage is always empty; the controller reads and writes nothing.
 *
 * The stub implementation is needed for synchronizing configuration during
 * installation of a module, in which case all configuration being shipped with
 * the module is known to be new. Therefore, the module installation process is
 * able to short-circuit the full diff against the active configuration; the
 * diff would yield all currently available configuration as items to remove,
 * since they do not exist in the module's default configuration directory.
 *
 * This also can be used for testing purposes.
 */
class NullStorage implements StorageInterface {

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $raw;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    // No op.
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return '';
  }

}
