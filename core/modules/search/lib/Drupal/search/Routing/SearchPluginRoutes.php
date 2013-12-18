<?php

/**
 * @file
 * Contains \Drupal\search\Routing\SearchPluginRoutes.
 */

namespace Drupal\search\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\search\SearchPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for search.
 */
class SearchPluginRoutes implements ContainerInjectionInterface {

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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = array();
    foreach ($this->searchManager->getActiveDefinitions() as $plugin_id => $search_info) {
      $routes["search.view_$plugin_id"] = new Route(
        'search/' . $search_info['path'] . '/{keys}',
        array(
          '_content' => 'Drupal\search\Controller\SearchController::view',
          '_title' => $search_info['title'],
          'plugin_id' => $plugin_id,
          'keys' => '',
        ),
        array(
          'keys' => '.+',
          '_search_plugin_view_access' => $plugin_id,
          '_permission' => 'search content',
        )
      );
    }
    return $routes;
  }

}
