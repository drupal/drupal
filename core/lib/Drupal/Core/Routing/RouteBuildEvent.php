<?php

namespace Drupal\Core\Routing;

use Drupal\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;

/**
 * Represents route building information as event.
 */
class RouteBuildEvent extends Event {

  /**
   * The route collection.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * Constructs a RouteBuildEvent object.
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *   The route collection.
   */
  public function __construct(RouteCollection $route_collection) {
    $this->routeCollection = $route_collection;
  }

  /**
   * Gets the route collection.
   */
  public function getRouteCollection() {
    return $this->routeCollection;
  }

}
