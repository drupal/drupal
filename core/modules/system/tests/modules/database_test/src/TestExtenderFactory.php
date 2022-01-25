<?php

namespace Drupal\database_test;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Test select extender factory.
 */
class TestExtenderFactory {

  /**
   * Returns a test query extender.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select query object.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   *
   * @return \Drupal\Core\Database\Query\SelectExtender
   *   A test query extender.
   */
  public function get(SelectInterface $query, Connection $connection): SelectExtender {
    return new SelectExtender($query, $connection);
  }

}
