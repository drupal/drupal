<?php

/**
 * Definition of \Drupal\router_test\RouteTestSubscriber.
 */

namespace Drupal\router_test;

use \Drupal\Core\Routing\RouteBuildEvent;
use \Drupal\Core\Routing\RoutingEvents;
use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Symfony\Component\Routing\Route;

/**
 * Listens to the dynamic route event and add a test route.
 */
class RouteTestSubscriber implements EventSubscriberInterface {

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'dynamicRoutes';
    $events[RoutingEvents::ALTER] = 'alterRoutes';
    return $events;
  }

  /**
   * Adds a dynamic test route.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function dynamicRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $route = new Route('/router_test/test5', array(
      '_content' => '\Drupal\router_test\TestControllers::test5'
    ), array(
      '_access' => 'TRUE'
    ));
    $collection->add('router_test_5', $route);
  }

  /**
   * Alters an existing test route.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The altered route collection.
   */
  public function alterRoutes(RouteBuildEvent $event) {
    if ($event->getModule() == 'router_test') {
      $collection = $event->getRouteCollection();
      $route = $collection->get('router_test_6');
      // Change controller method from test1 to test5.
      $route->setDefault('_controller', '\Drupal\router_test\TestControllers::test5');
    }
  }
}
