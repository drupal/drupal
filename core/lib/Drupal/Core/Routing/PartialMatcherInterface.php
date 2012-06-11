<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * A PartialMatcher works like a UrlMatcher, but will return multiple candidate routes.
 */
interface PartialMatcherInterface {

  /**
   * Matches a request against multiple routes.
   *
   * @param Request $request
   *   A Request object against which to match.
   *
   * @return RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchByRequest(Request $request);
}
