<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\PathMatcherInterface.
 */

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
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection against which to match.
   *
   * @return \Drupal\Core\Routing\PartialMatcherInterface
   *   The current matcher.
   */
  public function setCollection(RouteCollection $collection);

  /**
   * Matches a request against multiple routes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Request object against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchRequestPartial(Request $request);
}
