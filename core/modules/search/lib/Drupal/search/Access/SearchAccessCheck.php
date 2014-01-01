<?php

/**
 * @file
 * Contains Drupal\search\Access\SearchAccessCheck
 */

namespace Drupal\search\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search\SearchPluginManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Checks access for viewing search.
 */
class SearchAccessCheck implements AccessInterface {

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
  public function access(Route $route, Request $request, AccountInterface $account) {
    return $this->searchManager->getActiveDefinitions() ? static::ALLOW : static::DENY;
  }

}
