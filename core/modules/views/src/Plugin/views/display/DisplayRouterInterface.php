<?php

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
   *   The route collection.
   *
   * @return array
   *   Returns a list of "$view_id.$display_id" elements which got overridden.
   */
  public function alterRoutes(RouteCollection $collection);

  /**
   * Generates a URL to this display.
   *
   * @return \Drupal\Core\Url
   *   A URL object for the display.
   */
  public function getUrlInfo();

  /**
   * Returns the route name for the display.
   *
   * The default route name for a display is views.$view_id.$display_id. Some
   * displays may override existing routes; in these cases, the route that is
   * overridden is returned instead.
   *
   * @return string
   *   The name of the route
   *
   * @see \Drupal\views\Plugin\views\display\DisplayRouterInterface::alterRoutes()
   * @see \Drupal\views\Plugin\views\display\DisplayRouterInterface::getAlteredRouteNames()
   */
  public function getRouteName();

  /**
   * Returns the list of routes overridden by Views.
   *
   * @return string[]
   *   An array of overridden route names. The keys are in the form
   *   view_id.display_id and the values are the route names.
   *
   * @see \Drupal\views\Plugin\views\display\DisplayRouterInterface::alterRoutes()
   */
  public function getAlteredRouteNames();

}
