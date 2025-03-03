<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the route provider interface.
 */
interface RouteProviderInterface {

  /**
   * Finds routes that may potentially match the request.
   *
   * This may return a mixed list of class instances, but all routes returned
   * must extend the core Symfony route. The classes may also implement
   * RouteObjectInterface to link to a content document.
   *
   * This method may not throw an exception based on implementation specific
   * restrictions on the URL. That case is considered a not found - returning
   * an empty array. Exceptions are only used to abort the whole request in
   * case something is seriously broken, like the storage backend being down.
   *
   * Note that implementations may not implement an optimal matching
   * algorithm, simply a reasonable first pass.  That allows for potentially
   * very large route sets to be filtered down to likely candidates, which
   * may then be filtered in memory more completely.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   All Routes that could potentially match $request.
   *   Empty collection if nothing can match
   */
  public function getRouteCollectionForRequest(Request $request);

  /**
   * Find the route using the provided route name.
   *
   * @param string $name
   *   The route name to fetch.
   *
   * @return \Symfony\Component\Routing\Route
   *   The Symfony route object.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   If a matching route cannot be found.
   */
  public function getRouteByName($name);

  /**
   * Find many routes by their names using the provided list of names.
   *
   * Note that this method may not throw an exception if some of the routes
   * are not found or are not actually Route instances. It will just return the
   * list of those Route instances it found.
   *
   * This method exists in order to allow performance optimizations. The
   * simple implementation could be to just repeatedly call
   * $this->getRouteByName() while catching and ignoring eventual exceptions.
   *
   * If $names is null, this method SHOULD return a collection of all routes
   * known to this provider. If there are many routes to be expected, usage of
   * a lazy loading collection is recommended. A provider MAY only return a
   * subset of routes to e.g. support paging or other concepts.
   *
   * @param array|null $names
   *   The list of names to retrieve, In case of null, the provider will
   *   determine what routes to return.
   *
   * @return \Symfony\Component\Routing\Route|\Symfony\Component\Routing\Alias[]
   *   Iterable list with the keys being the names from the $names array
   */
  public function getRoutesByNames($names);

  /**
   * Get all routes which match a certain pattern.
   *
   * @param string $pattern
   *   The route pattern to search for (contains {} as placeholders).
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   Returns a route collection of matching routes. The collection may be
   *   empty and will be sorted from highest to lowest fit (match of path parts)
   *   and then in ascending order by route name for routes with the same fit.
   */
  public function getRoutesByPattern($pattern);

  /**
   * Returns all the routes on the system.
   *
   * Usage of this method is discouraged for performance reasons. If possible,
   * use RouteProviderInterface::getRoutesByNames() or
   * RouteProviderInterface::getRoutesByPattern() instead.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An iterator of routes keyed by route name.
   */
  public function getAllRoutes();

  /**
   * Resets the route provider object.
   */
  public function reset();

  /**
   * Gets aliases for a route name.
   *
   * The aliases can be found using the ::getAliases() method of the returned
   * route collection.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return iterable<\Symfony\Component\Routing\Alias>
   *   Iterable list of aliases for the given route name.
   */
  public function getRouteAliases(string $route_name): iterable;

}
