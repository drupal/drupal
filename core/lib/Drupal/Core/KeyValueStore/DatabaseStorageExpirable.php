<?php

namespace Drupal\Core\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines a default key/value store implementation for expiring items.
 *
 * This key/value store implementation uses the database to store key/value
 * data with an expire date.
 */
class DatabaseStorageExpirable extends DatabaseStorage implements KeyValueStoreExpirableInterface {

  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The name of the SQL table to use, defaults to key_value_expire.
   */
  public function __construct($collection, SerializationInterface $serializer, Connection $connection, $table = 'key_value_expire') {
    parent::__construct($collection, $serializer, $connection, $table);
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    try {
      return (bool) $this->connection->query('SELECT 1 FROM {' . $this->connection->escapeTable($this->table) . '} WHERE [collection] = :collection AND [name] = :key AND [expire] > :now', [
        ':collection' => $this->collection,
        ':key' => $key,
        ':now' => REQUEST_TIME,
      ])->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    try {
      $values = $this->connection->query(
        'SELECT [name], [value] FROM {' . $this->connection->escapeTable($this->table) . '} WHERE [expire] > :now AND [name] IN ( :keys[] ) AND [collection] = :collection',
        [
          ':now' => REQUEST_TIME,
          ':keys[]' => $keys,
          ':collection' => $this->collection,
        ])->fetchAllKeyed();
      return array_map([$this->serializer, 'decode'], $values);
    }
    catch (\Exception $e) {
      // @todo: Perhaps if the database is never going to be available,
      // key/value requests should return FALSE in order to allow exception
      // handling to occur but for now, keep it an array, always.
      // https://www.drupal.org/node/2787737
      $this->catchException($e);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    try {
      $values = $this->connection->query(
        'SELECT [name], [value] FROM {' . $this->connection->escapeTable($this->table) . '} WHERE [collection] = :collection AND [expire] > :now',
        [
          ':collection' => $this->collection,
          ':now' => REQUEST_TIME,
        ])->fetchAllKeyed();
      return array_map([$this->serializer, 'decode'], $values);
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
    return [];
  }

  /**
   * Saves a value for a given key with a time to live.
   *
   * This will be called by setWithExpire() within a try block.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  protected function doSetWithExpire($key, $value, $expire) {
    $this->connection->merge($this->table)
      ->keys([
        'name' => $key,
        'collection' => $this->collection,
      ])
      ->fields([
        'value' => $this->serializer->encode($value),
        'expire' => REQUEST_TIME + $expire,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function setWithExpire($key, $value, $expire) {
    try {
      $this->doSetWithExpire($key, $value, $expire);
    }
    catch (\Exception $e) {
      // If there was an exception, then try to create the table.
      if ($this->ensureTableExists()) {
        $this->doSetWithExpire($key, $value, $expire);
      }
      else {
        throw $e;
      }
    }
  }

  /**
   * Sets a value for a given key with a time to live if it does not yet exist.
   *
   * This will be called by setWithExpireIfNotExists() within a try block.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   * @param int $expire
   *   The time to live for items, in seconds.
   *
   * @return bool
   *   TRUE if the data was set, or FALSE if it already existed.
   */
  protected function doSetWithExpireIfNotExists($key, $value, $expire) {
    if (!$this->has($key)) {
      $this->setWithExpire($key, $value, $expire);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setWithExpireIfNotExists($key, $value, $expire) {
    try {
      return $this->doSetWithExpireIfNotExists($key, $value, $expire);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        return $this->doSetWithExpireIfNotExists($key, $value, $expire);
      }
      else {
        throw $e;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMultipleWithExpire(array $data, $expire) {
    foreach ($data as $key => $value) {
      $this->setWithExpire($key, $value, $expire);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    parent::deleteMultiple($keys);
  }

  /**
   * Defines the schema for the key_value_expire table.
   */
  public static function schemaDefinition() {
    return [
      'description' => 'Generic key/value storage table with an expiration.',
      'fields' => [
        'collection' => [
          'description' => 'A named collection of key and value pairs.',
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        'name' => [
          // KEY is an SQL reserved word, so use 'name' as the key's field name.
          'description' => 'The key of the key/value pair.',
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        'value' => [
          'description' => 'The value of the key/value pair.',
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'expire' => [
          'description' => 'The time since Unix epoch in seconds when this item expires. Defaults to the maximum possible time.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 2147483647,
        ],
      ],
      'primary key' => ['collection', 'name'],
      'indexes' => [
        'expire' => ['expire'],
      ],
    ];
  }

}
