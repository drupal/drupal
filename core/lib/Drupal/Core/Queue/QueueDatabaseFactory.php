<?php

namespace Drupal\Core\Queue;

use Drupal\Core\Database\Connection;

/**
 * Defines the queue factory for the database backend.
 */
class QueueDatabaseFactory implements QueueFactoryInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the queue table.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return new DatabaseQueue($name, $this->connection);
  }

}
