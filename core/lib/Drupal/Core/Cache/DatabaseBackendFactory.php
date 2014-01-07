<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\DatabaseBackendFactory.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Connection;

class DatabaseBackendFactory implements CacheFactoryInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the DatabaseBackendFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\DatabaseBackend
   *   The cache backend object for the specified cache bin.
   */
  function get($bin) {
    return new DatabaseBackend($this->connection, $bin);
  }

}
