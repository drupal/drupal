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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The search text processor service.
   *
   * @var \Drupal\search\SearchTextProcessorInterface
   */
  protected $searchTextProcessor;

  /**
   * Constructs a ViewsSearchQueryFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\search\SearchTextProcessorInterface $search_text_processor
   *   The search text processor service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SearchTextProcessorInterface $search_text_processor) {
    $this->configFactory = $config_factory;
    $this->searchTextProcessor = $search_text_processor;
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
