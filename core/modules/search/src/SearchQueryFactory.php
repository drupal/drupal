<?php

namespace Drupal\search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Select extender factory for search queries.
 */
class SearchQueryFactory {

  /**
   * Constructs a SearchQueryFactory object.
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
   * Returns a query extender for search queries.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select query object.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   *
   * @return Drupal\search\SearchQuery
   *   A query extender for search queries.
   */
  public function get(SelectInterface $query, Connection $connection): SearchQuery {
    return new SearchQuery($query, $connection, $this->configFactory, $this->searchTextProcessor);
  }

}
