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
   * The module name that provides the route.
   *
   * @var string
   */
  protected $module;

  /**
   * Constructs a RouteBuildEvent object.
   */
  public function __construct(RouteCollection $route_collection, $module) {
    $this->routeCollection = $route_collection;
    $this->module = $module;
  }

  /**
   * Gets the route collection.
   */
  public function getRouteCollection() {
    return $this->routeCollection;
  }

  /**
   * Gets the module that provides the route.
   */
  public function getModule() {
    return $this->module;
  }

}
