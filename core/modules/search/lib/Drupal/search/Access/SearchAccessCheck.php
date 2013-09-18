<?php

/**
 * @file
 * Contains Drupal\search\Access\SearchAccessCheck
 */

namespace Drupal\search\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\search\SearchPluginManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Checks access for viewing search.
 */
class SearchAccessCheck implements StaticAccessCheckInterface {

  /**
   * The search plugin manager.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchManager;

  /**
   * Contructs a new search access check.
   *
   * @param SearchPluginManager $search_plugin_manager
   *   The search plugin manager.
   */
  public function __construct(SearchPluginManager $search_plugin_manager) {
    $this->searchManager = $search_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_search_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    return $this->searchManager->getActiveDefinitions() ? static::ALLOW : static::DENY;
  }

}
