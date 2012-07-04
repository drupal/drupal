<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * A PartialMatcher works like a UrlMatcher, but will return multiple candidate routes.
 */
interface PartialMatcherInterface {

  /**
   * Sets the route collection this matcher should use.
   *
   * @param RouteCollection $collection
   *   The collection against which to match.
   *
   * @return PartialMatcherInterface
   *   The current matcher.
   */
  public function setCollection(RouteCollection $collection);

  /**
   * Matches a request against multiple routes.
   *
   * @param Request $request
   *   A Request object against which to match.
   *
   * @return RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchRequestPartial(Request $request);
}
