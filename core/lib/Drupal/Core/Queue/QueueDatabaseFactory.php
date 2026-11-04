<?php

namespace Drupal\Core\Queue;

use Drupal\Core\Database\Connection;

/**
 * Defines the queue factory for the database backend.
 */
class QueueDatabaseFactory implements QueueFactoryInterface {

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the queue table.
   */
  public function __construct(
    protected Connection $connection,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return new DatabaseQueue($name, $this->connection);
  }

}
