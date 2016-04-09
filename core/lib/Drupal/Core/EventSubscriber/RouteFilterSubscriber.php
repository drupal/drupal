<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Routing\LazyRouteFilter;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the filtered collection of route instances.
 */
class RouteFilterSubscriber implements EventSubscriberInterface {

  /**
   * The lazy route filter.
   *
   * @var \Drupal\Core\Routing\LazyRouteFilter
   */
  protected $routeFilter;

  /**
   * Constructs the RouteFilterSubscriber object.
   *
   * @param \Drupal\Core\Routing\LazyRouteFilter $route_filter
   *   The lazy route filter.
   */
  public function __construct(LazyRouteFilter $route_filter) {
    $this->routeFilter = $route_filter;
  }

  /**
   * Get the Response object from filtered route collection.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onRouteAlter(RouteBuildEvent $event) {
    $this->routeFilter->setFilters($event->getRouteCollection());
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = array('onRouteAlter', -300);
    return $events;
  }

}
