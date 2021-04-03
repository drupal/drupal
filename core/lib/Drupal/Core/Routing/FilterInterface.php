<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route filter service to filter down the collection of route instances.
 */
interface FilterInterface {

  /**
   * Filters the route collection against a request and returns all matching
   * routes.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection against which to match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Request object against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A non-empty RouteCollection of matched routes
   *
   * @throws ResourceNotFoundException
   *   If none of the routes in $collection matches $request. This is a
   *   performance optimization to not continue the match process when a match
   *   will no longer be possible.
   */
  public function filter(RouteCollection $collection, Request $request);

}
