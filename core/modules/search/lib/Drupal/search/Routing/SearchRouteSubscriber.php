<?php

/**
 * @file
 * Contains Drupal\search\Routing\SearchRouteSubscriber
 */

namespace Drupal\search\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\search\SearchPluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for search.
 */
class SearchRouteSubscriber implements EventSubscriberInterface {

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
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }

  /**
   * Adds routes for search.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();

    foreach ($this->searchManager->getActiveDefinitions() as $plugin_id => $search_info) {
      $path = 'search/' . $search_info['path'] . '/{keys}';
      $defaults = array(
        '_content' => 'Drupal\search\Controller\SearchController::searchViewPlugin',
        'plugin_id' => $plugin_id,
        'keys' => '',
      );
      $requirements = array(
        'keys' => '.+',
        '_search_plugin_view_access' => $plugin_id,
        '_permission' => 'search content',
      );
      $options = array(
        '_access_mode' => 'ALL',
      );
      $route = new Route($path, $defaults, $requirements, $options);
      $collection->add('search.view_' . $plugin_id, $route);
    }
  }

}
