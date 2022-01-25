<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;
use Drupal\Core\Pager\PagerManagerInterface;

/**
 * Select extender factory for pager queries.
 */
class PagerSelectExtenderFactory {

  /**
   * Constructs a PagerSelectExtenderFactory object.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager service.
   */
  public function __construct(
    protected PagerManagerInterface $pagerManager
  ) {
  }

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
    return new PagerSelectExtender($query, $connection, $this->pagerManager);
  }

}
