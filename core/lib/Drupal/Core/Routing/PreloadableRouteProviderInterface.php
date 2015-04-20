<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\PreloadableRouteProviderInterface.
 */

namespace Drupal\Core\Routing;

/**
 * Extends the router provider interface to pre-load routes.
 */
interface PreloadableRouteProviderInterface extends RouteProviderInterface {

  /**
   * Pre-load routes by their names using the provided list of names.
   *
   * This method exists in order to allow performance optimizations. It allows
   * pre-loading serialized routes that may latter be retrieved using
   * ::getRoutesByName()
   *
   * @param string[] $names
   *   Array of route names to load.
   */
  public function preLoadRoutes($names);

}
