<?php

namespace Drupal\corefake;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\PagerSelectExtender;

/**
 * Select extender factory for pager queries.
 */
class PagerSelectExtenderFactory {

  /**
   * Returns a query extender for pager queries.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select query object.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   *
   * @return \Drupal\Core\Database\Query\PagerSelectExtender
   *   A query extender for pager queries.
   */
  public function get(SelectInterface $query, Connection $connection): PagerSelectExtender {
    return new PagerSelectExtender($query, $connection);
  }

}
