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
   * @return array
   *   An array of parameters
   */
  public function matchRequest(Request $request);
}
