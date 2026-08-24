<?php

namespace Drupal\Core\Routing;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a base implementation for routing event subscribers.
 */
abstract class RouteSubscriberBase implements EventSubscriberInterface {

  /**
   * Provides new routes during the RoutingEvents::STATIC phase.
   *
   * This is the object-oriented equivalent of a 'route_callbacks' entry in a
   * *.routing.yml file.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A collection of new routes to add.
   */
  protected function buildRoutes(): RouteCollection {
    return new RouteCollection();
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::STATIC] = 'onBuildRoutes';
    $events[RoutingEvents::ALTER] = 'onAlterRoutes';
    return $events;
  }

  /**
   * Delegates the route building to self::buildRoutes().
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onBuildRoutes(RouteBuildEvent $event): void {
    $event->getRouteCollection()->addCollection($this->buildRoutes());
  }

  /**
   * Delegates the route altering to self::alterRoutes().
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onAlterRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $this->alterRoutes($collection);
  }

}
