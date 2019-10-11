<?php

namespace Drupal\path\Routing;

use Drupal\Core\Routing\BcRoute;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides backwards-compatible routes for the path module.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * Provides routes on route rebuild time.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRouteEvent(RouteBuildEvent $event) {
    $route_collection = $event->getRouteCollection();

    $route_collection->add('path.admin_add', new BcRoute());
    $route_collection->add('path.admin_edit', new BcRoute());
    $route_collection->add('path.delete', new BcRoute());
    $route_collection->add('path.admin_overview', new BcRoute());
    $route_collection->add('path.admin_overview_filter', new BcRoute());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC][] = ['onDynamicRouteEvent', 0];
    return $events;
  }

}
