<?php

namespace Drupal\search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Select extender factory for views search queries.
 */
class ViewsSearchQueryFactory {

  /**
   * Constructs a ViewsSearchQueryFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\search\SearchTextProcessorInterface $searchTextProcessor
   *   The search text processor service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected SearchTextProcessorInterface $searchTextProcessor
  ) {
  }

  /**
   * Returns a query extender for views search queries.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select query object.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   *
   * @return Drupal\search\ViewsSearchQuery
   *   A query extender for views search queries.
   */
  public function get(SelectInterface $query, Connection $connection): ViewsSearchQuery {
    return new ViewsSearchQuery($query, $connection, $this->configFactory, $this->searchTextProcessor);
  }

}
