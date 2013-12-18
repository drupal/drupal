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
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   * @param string $provider
   *   The provider these routes belong to. For dynamically added routes, the
   *   provider name will be 'dynamic_routes'.
   */
  protected function alterRoutes(RouteCollection $collection, $provider) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = 'onAlterRoutes';
    return $events;
  }

  /**
   * Delegates the route altering to self::alterRoutes().
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onAlterRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $this->alterRoutes($collection, $event->getProvider());
  }

}
