<?php

/**
 * @file
 * Definition of Drupal\Core\Config\NullStorage.
 */

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
   * Implements Drupal\Core\Config\StorageInterface::exists().
   */
  public function exists($name) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   */
  public function read($name) {
    return array();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::readMultiple().
   */
  public function readMultiple(array $names) {
    return array();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::write().
   */
  public function write($name, array $data) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::delete().
   */
  public function delete($name) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::rename().
   */
  public function rename($name, $new_name) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   */
  public function encode($data) {
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   */
  public function decode($raw) {
    return $raw;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   */
  public function listAll($prefix = '') {
    return array();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::deleteAll().
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
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return '';
  }

}
