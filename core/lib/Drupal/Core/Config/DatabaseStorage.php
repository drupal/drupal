<?php

/**
 * @file
 * Contains \Drupal\Core\Config\DatabaseStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Defines the Database storage.
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
   * Constructs a new DatabaseStorage.
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
    try {
      return (bool) $this->connection->queryRange('SELECT 1 FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name = :name', 0, 1, array(
        ':name' => $name,
      ), $this->options)->fetchField();
    }
    catch (\Exception $e) {
      // If we attempt a read without actually having the database or the table
      // available, just return FALSE so the caller can handle it.
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $data = FALSE;
    try {
      $raw = $this->connection->query('SELECT data FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name = :name', array(':name' => $name), $this->options)->fetchField();
      if ($raw !== FALSE) {
        $data = $this->decode($raw);
      }
    }
    catch (\Exception $e) {
      // If we attempt a read without actually having the database or the table
      // available, just return FALSE so the caller can handle it.
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = array();
    try {
      $list = $this->connection->query('SELECT name, data FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name IN (:names)', array(':names' => $names), $this->options)->fetchAllKeyed();
      foreach ($list as &$data) {
        $data = $this->decode($data);
      }
    }
    catch (\Exception $e) {
      // If we attempt a read without actually having the database or the table
      // available, just return an empty array so the caller can handle it.
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    $data = $this->encode($data);
    try {
      return $this->doWrite($name, $data);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        return $this->doWrite($name, $data);
      }
      // Some other failure that we can not recover from.
      throw $e;
    }
  }

  /**
   * Helper method so we can re-try a write.
   *
   * @param string $name
   *   The config name.
   * @param string $data
   *   The config data, already dumped to a string.
   *
   * @return bool
   */
  protected function doWrite($name, $data) {
    $options = array('return' => Database::RETURN_AFFECTED) + $this->options;
    return (bool) $this->connection->merge($this->table, $options)
      ->key('name', $name)
      ->fields(array('data' => $data))
      ->execute();
  }

  /**
   * Check if the config table exists and create it if not.
   *
   * @return bool
   *   TRUE if the table was created, FALSE otherwise.
   *
   * @throws \Drupal\Core\Config\StorageException
   *   If a database error occurs.
   */
  protected function ensureTableExists()  {
    try {
      if (!$this->connection->schema()->tableExists($this->table)) {
        $this->connection->schema()->createTable($this->table, static::schemaDefinition());
        return TRUE;
      }
    }
    // If another process has already created the config table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    catch (\Exception $e) {
      throw new StorageException($e->getMessage(), NULL, $e);
    }
    return FALSE;
  }

  /**
   * Defines the schema for the configuration table.
   */
  protected static function schemaDefinition() {
    $schema = array(
      'description' => 'The base table for configuration data.',
      'fields' => array(
        'name' => array(
          'description' => 'Primary Key: Unique config object name.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'data' => array(
          'description' => 'A serialized configuration object data.',
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
        ),
      ),
      'primary key' => array('name'),
    );
    return $schema;
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
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    try {
      return $this->connection->query('SELECT name FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name LIKE :name', array(
        ':name' => $this->connection->escapeLike($prefix) . '%',
      ), $this->options)->fetchCol();
    }
    catch (\Exception $e) {
      return array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    try {
      $options = array('return' => Database::RETURN_AFFECTED) + $this->options;
      return (bool) $this->connection->delete($this->table, $options)
        ->condition('name', $prefix . '%', 'LIKE')
        ->execute();
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }
}
