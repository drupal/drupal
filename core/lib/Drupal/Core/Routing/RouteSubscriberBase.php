<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RouteSubscriberBase
 */

namespace Drupal\Core\Routing;

use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a base implementation for RouteSubscriber.
 */
abstract class RouteSubscriberBase implements EventSubscriberInterface {

  /**
   * Provides new routes by adding them to the collection.
   *
   * Subclasses should use this method and add \Symfony\Component\Routing\Route
   * objects with $collection->add($route);.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function routes(RouteCollection $collection) {
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   * @param string $module
   *   The module these routes belong to. For dynamically added routes, the
   *   module name will be 'dynamic_routes'.
   */
  protected function alterRoutes(RouteCollection $collection, $module) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'onDynamicRoutes';
    $events[RoutingEvents::ALTER] = 'onAlterRoutes';
    return $events;
  }

  /**
   * Delegates the route gathering to self::routes().
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $this->routes($collection);
  }

  /**
   * Delegates the route altering to self::alterRoutes().
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onAlterRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $this->alterRoutes($collection, $event->getModule());
  }

}
