<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a default value for the HTTP method restriction on routes.
 *
 * Most routes will only deal with GET and POST requests, so we restrict them to
 * those two if nothing else is specified. This is necessary to give other
 * routes a chance during the route matching process when they are listening
 * for example to DELETE requests on the same path. A typical use case are REST
 * web service routes that use the full spectrum of HTTP methods.
 */
class RouteMethodSubscriber implements EventSubscriberInterface {

  /**
   * Sets a default value of GET|POST for the _method route property.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event containing the build routes.
   */
  public function onRouteBuilding(RouteBuildEvent $event) {
    foreach ($event->getRouteCollection() as $route) {
      $methods = $route->getMethods();
      if (empty($methods)) {
        $route->setMethods(array('GET', 'POST'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Set a higher priority to ensure that routes get the default HTTP methods
    // as early as possible.
    $events[RoutingEvents::ALTER][] = array('onRouteBuilding', 5000);
    return $events;
  }

}
