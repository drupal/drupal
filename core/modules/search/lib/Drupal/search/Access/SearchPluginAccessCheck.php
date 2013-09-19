<?php

/**
 * @file
 * Contains Drupal\search\Access\SearchPluginAccessCheck
 */

namespace Drupal\search\Access;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Route access check for search plugins.
 */
class SearchPluginAccessCheck extends SearchAccessCheck {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_search_plugin_view_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $account = \Drupal::currentUser();
    $plugin_id = $route->getRequirement('_search_plugin_view_access');
    return $this->searchManager->pluginAccess($plugin_id, $account) ? static::ALLOW : static::DENY;
  }

}
