<?php

namespace Drupal\Core\Queue;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines the key/value store factory for the database backend.
 */
class QueueDatabaseFactory {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $connection, TimeInterface $time = NULL) {
    $this->connection = $connection;

    if (!$time) {
      @trigger_error('The time service must be passed to ' . __NAMESPACE__ . '\DatabaseQueue::__construct(). It was added in drupal:9.3.0 and will be required before drupal:10.0.0. See https://www.drupal.org/node/3161659', E_USER_DEPRECATED);
      $time = \Drupal::time();
    }
    $this->time = $time;
  }

  /**
   * Constructs a new queue object for a given name.
   *
   * @param string $name
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\Queue\DatabaseQueue
   *   A key/value store implementation for the given $collection.
   */
  public function get($name) {
    return new DatabaseQueue($name, $this->connection, $this->time);
  }

}
