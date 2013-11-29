<?php

/**
 * @file
 * Contains \Drupal\search\Routing\SearchRouteSubscriber.
 */

namespace Drupal\search\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\search\SearchPluginManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides dynamic routes for search.
 */
class SearchRouteSubscriber extends RouteSubscriberBase {

  /**
   * The search plugin manager.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchManager;

  /**
   * Constructs a new search route subscriber.
   *
   * @param \Drupal\search\SearchPluginManager $search_plugin_manager
   *   The search plugin manager.
   */
  public function __construct(SearchPluginManager $search_plugin_manager) {
    $this->searchManager = $search_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    foreach ($this->searchManager->getActiveDefinitions() as $plugin_id => $search_info) {
      $path = 'search/' . $search_info['path'] . '/{keys}';
      $defaults = array(
        '_content' => 'Drupal\search\Controller\SearchController::view',
        '_title' => $search_info['title'],
        'plugin_id' => $plugin_id,
        'keys' => '',
      );
      $requirements = array(
        'keys' => '.+',
        '_search_plugin_view_access' => $plugin_id,
        '_permission' => 'search content',
      );
      $route = new Route($path, $defaults, $requirements);
      $collection->add('search.view_' . $plugin_id, $route);
    }
  }

}
