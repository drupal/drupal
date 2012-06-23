<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * A PartialMatcher works like a UrlMatcher, but will return multiple candidate routes.
 */
interface FinalMatcherInterface {

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
  public function matchRequest(Request $request);
}
