<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteBuildEvent.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\EventDispatcher\Event;
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
   * The provider of this route collection.
   *
   * @var string
   */
  protected $provider;

  /**
   * Constructs a RouteBuildEvent object.
   */
  public function __construct(RouteCollection $route_collection, $provider) {
    $this->routeCollection = $route_collection;
    $this->provider = $provider;
  }

  /**
   * Gets the route collection.
   */
  public function getRouteCollection() {
    return $this->routeCollection;
  }

  /**
   * Gets the provider for this route collection.
   */
  public function getProvider() {
    return $this->provider;
  }

}
