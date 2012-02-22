<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\DrupalConfigVerifiedStorage;
use Exception;

/**
 * Represents an SQL-based configuration storage object.
 */
class DrupalVerifiedStorageSQL extends DrupalConfigVerifiedStorage {

  /**
   * Overrides DrupalConfigVerifiedStorage::read().
   */
  public function read() {
    // There are situations, like in the installer, where we may attempt a
    // read without actually having the database available. In this case,
    // catch the exception and just return an empty array so the caller can
    // handle it if need be.
    try {
      $result = db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $this->name))->fetchField();
      return $result;
    } catch (Exception $e) {
      return array();
    }
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::writeToActive().
   */
  public function writeToActive($data) {
    return db_merge('config')
      ->key(array('name' => $this->name))
      ->fields(array('data' => $data))
      ->execute();
  }

  /**
   * @todo
   */
  public function deleteFromActive() {
    db_delete('config')
      ->condition('name', $this->name)
      ->execute();
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::getNamesWithPrefix().
   */
  static public function getNamesWithPrefix($prefix = '') {
    return db_query('SELECT name FROM {config} WHERE name LIKE :name', array(':name' => db_like($prefix) . '%'))->fetchCol();
  }
}
