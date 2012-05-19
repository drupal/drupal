<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\StorageBase;
use Exception;

/**
 * Represents an SQL-based configuration storage object.
 */
class DatabaseStorage extends StorageBase {

  /**
   * Implements StorageInterface::read().
   */
  public function read() {
    // There are situations, like in the installer, where we may attempt a
    // read without actually having the database available. In this case,
    // catch the exception and just return an empty array so the caller can
    // handle it if need be.
    $data = array();
    try {
      $raw = db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $this->name))->fetchField();
      if ($raw !== FALSE) {
        $data = $this->decode($raw);
      }
    }
    catch (Exception $e) {
    }
    return $data;
  }

  /**
   * Implements StorageInterface::writeToActive().
   */
  public function writeToActive($data) {
    $data = $this->encode($data);
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
   * Implements StorageInterface::encode().
   */
  public static function encode($data) {
    return serialize($data);
  }

  /**
   * Implements StorageInterface::decode().
   */
  public static function decode($raw) {
    return unserialize($raw);
  }

  /**
   * Implements StorageInterface::getNamesWithPrefix().
   */
  static public function getNamesWithPrefix($prefix = '') {
    return db_query('SELECT name FROM {config} WHERE name LIKE :name', array(':name' => db_like($prefix) . '%'))->fetchCol();
  }
}
