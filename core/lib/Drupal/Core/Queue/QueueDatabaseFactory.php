<?php

/**
 * @file
 * Contains \Drupal\Core\Queue\QueueDatabaseFactory.
 */

namespace Drupal\Core\Queue;

use Drupal\Core\Database\Connection;

/**
 * Defines the key/value store factory for the database backend.
 */
class QueueDatabaseFactory {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Constructs a new queue object for a given name.
   *
   * @param string $name
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to run against.
   *
   * @return \Drupal\Core\Queue\DatabaseQueue
   *   A key/value store implementation for the given $collection.
   */
  public function get($name) {
    return new DatabaseQueue($name, $this->connection);
  }
}
