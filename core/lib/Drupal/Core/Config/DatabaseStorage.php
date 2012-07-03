<?php

/**
 * @file
 * Definition of Drupal\Core\Config\DatabaseStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Database\Database;
use Exception;

/**
 * Defines the Database storage controller.
 */
class DatabaseStorage implements StorageInterface {

  /**
   * Database connection options for this storage controller.
   *
   * - connection: The connection key to use.
   * - target: The target on the connection to use.
   *
   * @var array
   */
  protected $options;

  /**
   * Implements Drupal\Core\Config\StorageInterface::__construct().
   */
  public function __construct(array $options = array()) {
    $options += array(
      'connection' => 'default',
      'target' => 'default',
    );
    $this->options = $options;
  }

  /**
   * Returns the database connection to use.
   */
  protected function getConnection() {
    return Database::getConnection($this->options['target'], $this->options['connection']);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   *
   * @throws PDOException
   * @throws Drupal\Core\Database\DatabaseExceptionWrapper
   *   Only thrown in case $this->options['throw_exception'] is TRUE.
   */
  public function read($name) {
    $data = FALSE;
    // There are situations, like in the installer, where we may attempt a
    // read without actually having the database available. In this case,
    // catch the exception and just return an empty array so the caller can
    // handle it if need be.
    // @todo Remove this and use appropriate StorageDispatcher configuration in
    //   the installer instead.
    try {
      $raw = $this->getConnection()->query('SELECT data FROM {config} WHERE name = :name', array(':name' => $name), $this->options)->fetchField();
      if ($raw !== FALSE) {
        $data = $this->decode($raw);
      }
    }
    catch (Exception $e) {
    }
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::write().
   *
   * @throws PDOException
   *
   * @todo Ignore slave targets for data manipulation operations.
   */
  public function write($name, array $data) {
    $data = $this->encode($data);
    $options = array('return' => Database::RETURN_AFFECTED) + $this->options;
    return (bool) $this->getConnection()->merge('config', $options)
      ->key(array('name' => $name))
      ->fields(array('data' => $data))
      ->execute();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::delete().
   *
   * @throws PDOException
   *
   * @todo Ignore slave targets for data manipulation operations.
   */
  public function delete($name) {
    $options = array('return' => Database::RETURN_AFFECTED) + $this->options;
    return (bool) $this->getConnection()->delete('config', $options)
      ->condition('name', $name)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   */
  public static function encode($data) {
    return serialize($data);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   *
   * @throws ErrorException
   *   unserialize() triggers E_NOTICE if the string cannot be unserialized.
   */
  public static function decode($raw) {
    $data = @unserialize($raw);
    return is_array($data) ? $data : FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   *
   * @throws PDOException
   * @throws Drupal\Core\Database\DatabaseExceptionWrapper
   *   Only thrown in case $this->options['throw_exception'] is TRUE.
   */
  public function listAll($prefix = '') {
    return $this->getConnection()->query('SELECT name FROM {config} WHERE name LIKE :name', array(
      ':name' => db_like($prefix) . '%',
    ), $this->options)->fetchCol();
  }
}
