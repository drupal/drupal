<?php

/**
 * @file
 * Definition of Drupal\Core\Config\DatabaseStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;

/**
 * Defines the Database storage controller.
 */
class DatabaseStorage implements StorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The database table name.
   *
   * @var string
   */
  protected $table;

  /**
   * Additional database connection options to use in queries.
   *
   * @var array
   */
  protected $options = array();

  /**
   * Constructs a new DatabaseStorage controller.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   * @param string $table
   *   A database table name to store configuration data in.
   * @param array $options
   *   (optional) Any additional database connection options to use in queries.
   */
  public function __construct(Connection $connection, $table, array $options = array()) {
    $this->connection = $connection;
    $this->table = $table;
    $this->options = $options;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::exists().
   */
  public function exists($name) {
    return (bool) $this->connection->queryRange('SELECT 1 FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name = :name', 0, 1, array(
      ':name' => $name,
    ), $this->options)->fetchField();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   *
   * @throws PDOException
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Only thrown in case $this->options['throw_exception'] is TRUE.
   */
  public function read($name) {
    $data = FALSE;
    // There are situations, like in the installer, where we may attempt a
    // read without actually having the database available. In this case,
    // catch the exception and just return an empty array so the caller can
    // handle it if need be.
    try {
      $raw = $this->connection->query('SELECT data FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name = :name', array(':name' => $name), $this->options)->fetchField();
      if ($raw !== FALSE) {
        $data = $this->decode($raw);
      }
    }
    catch (\Exception $e) {
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    // There are situations, like in the installer, where we may attempt a
    // read without actually having the database available. In this case,
    // catch the exception and just return an empty array so the caller can
    // handle it if need be.
    $list = array();
    try {
      $list = $this->connection->query('SELECT name, data FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name IN (:names)', array(':names' => $names), $this->options)->fetchAllKeyed();
      foreach ($list as &$data) {
        $data = $this->decode($data);
      }
    }
    catch (Exception $e) {
    }
    return $list;
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
    return (bool) $this->connection->merge($this->table, $options)
      ->key('name', $name)
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
    return (bool) $this->connection->delete($this->table, $options)
      ->condition('name', $name)
      ->execute();
  }


  /**
   * Implements Drupal\Core\Config\StorageInterface::rename().
   *
   * @throws PDOException
   */
  public function rename($name, $new_name) {
    $options = array('return' => Database::RETURN_AFFECTED) + $this->options;
    return (bool) $this->connection->update($this->table, $options)
      ->fields(array('name' => $new_name))
      ->condition('name', $name)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   */
  public function encode($data) {
    return serialize($data);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   *
   * @throws ErrorException
   *   unserialize() triggers E_NOTICE if the string cannot be unserialized.
   */
  public function decode($raw) {
    $data = @unserialize($raw);
    return is_array($data) ? $data : FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   *
   * @throws PDOException
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Only thrown in case $this->options['throw_exception'] is TRUE.
   */
  public function listAll($prefix = '') {
    return $this->connection->query('SELECT name FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name LIKE :name', array(
      ':name' => db_like($prefix) . '%',
    ), $this->options)->fetchCol();
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::deleteAll().
   *
   * @throws PDOException
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Only thrown in case $this->options['throw_exception'] is TRUE.
   */
  public function deleteAll($prefix = '') {
    $options = array('return' => Database::RETURN_AFFECTED) + $this->options;
    return (bool) $this->connection->delete($this->table, $options)
      ->condition('name', $prefix . '%', 'LIKE')
      ->execute();
  }
}
