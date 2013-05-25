<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\DisplayRouterInterface.
 */

namespace Drupal\views\Plugin\views\display;

/**
 * Defines an interface for displays that can collect routes.
 *
 * In addition to implementing the interface, specify 'uses_routes' in the
 * plugin definition.
 */
use Symfony\Component\Routing\RouteCollection;

interface DisplayRouterInterface {

  /**
   * Adds the route entry of a view to the collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   A collection of routes that should be registered for this resource.
   */
  public function collectRoutes(RouteCollection $collection);

}
