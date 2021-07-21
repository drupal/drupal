<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Select extender factory for tablesort queries.
 */
class TableSortExtenderFactory {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a TableSortExtenderFactory object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Returns a query extender for tablesort queries.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select query object.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   *
   * @return \Drupal\Core\Database\Query\TableSortExtender
   *   A query extender for tablesort queries.
   */
  public function get(SelectInterface $query, Connection $connection): TableSortExtender {
    return new TableSortExtender($query, $connection, $this->requestStack);
  }

}
