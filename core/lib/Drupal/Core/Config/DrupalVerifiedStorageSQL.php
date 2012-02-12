<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\DrupalConfigVerifiedStorage;

/**
 * Represents an SQL-based configuration storage object.
 */
class DrupalVerifiedStorageSQL extends DrupalConfigVerifiedStorage {

  /**
   * Overrides DrupalConfigVerifiedStorage::read().
   */
  public function read() {
    // There are situations, like in the installer, where we may attempt a
    // read without actually having the database available. This is a
    // workaround and there is probably a better solution to be had at
    // some point.
    if (!empty($GLOBALS['databases']) && db_table_exists('config')) {
      return db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $this->name))->fetchField();
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
