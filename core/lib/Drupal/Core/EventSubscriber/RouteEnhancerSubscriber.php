<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\RouteEnhancerSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Routing\LazyRouteEnhancer;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the new routes before they get saved.
 */
class RouteEnhancerSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Routing\LazyRouteEnhancer
   */
  protected $routeEnhancer;

  /**
   * Constructs the RouteEnhancerSubscriber object.
   *
   * @param \Drupal\Core\Routing\LazyRouteEnhancer $route_enhancer
   *   The lazy route enhancer.
   */
  public function __construct(LazyRouteEnhancer $route_enhancer) {
    $this->routeEnhancer = $route_enhancer;
  }

  /**
   * Adds the route_enhancer object to the route collection.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onRouteAlter(RouteBuildEvent $event) {
    $this->routeEnhancer->setEnhancers($event->getRouteCollection());
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = array('onRouteAlter', -300);
    return $events;
  }

}
