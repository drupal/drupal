<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\DisplayRouterInterface.
 */

namespace Drupal\views\Plugin\views\display;

use Symfony\Component\Routing\RouteCollection;

/**
 * Defines an interface for displays that can collect routes.
 *
 * In addition to implementing the interface, specify 'uses_routes' in the
 * plugin definition.
 */
interface DisplayRouterInterface extends DisplayPluginInterface {

  /**
   * Adds the route entry of a view to the collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   A collection of routes that should be registered for this resource.
   */
  public function collectRoutes(RouteCollection $collection);

  /**
   * Alters a collection of routes and replaces definitions to the view.
   *
   * Most of the collections won't have the needed route, so by the return value
   * the method can specify to break the search.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *
   * @return array
   *   Returns a list of "$view_id.$display_id" elements which got overridden.
   */
  public function alterRoutes(RouteCollection $collection);

  /**
   * Generates an URL to this display.
   *
   * @return \Drupal\Core\Url
   *   A URL object for the display.
   */
  public function getUrlInfo();

}
