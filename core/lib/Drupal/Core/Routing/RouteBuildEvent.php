<?php

namespace Drupal\Core\Routing;

use Drupal\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;

/**
 * Represents route building information as event.
 */
class RouteBuildEvent extends Event {

  /**
   * Constructs a RouteBuildEvent object.
   *
   * @param \Symfony\Component\Routing\RouteCollection $routeCollection
   *   The route collection.
   */
  public function __construct(
    protected RouteCollection $routeCollection,
  ) {
  }

  /**
   * Gets the route collection.
   */
  public function getRouteCollection() {
    return $this->routeCollection;
  }

}
