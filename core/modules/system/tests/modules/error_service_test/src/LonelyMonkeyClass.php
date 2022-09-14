<?php

namespace Drupal\error_service_test;

use Drupal\Core\Database\Connection;

/**
 * A class with a single dependency.
 */
class LonelyMonkeyClass {

  /**
   * The database connection.
   */
  protected $connection;

  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

}
