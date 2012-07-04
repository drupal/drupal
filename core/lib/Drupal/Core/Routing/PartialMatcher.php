<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Utility base class for partial matchers.
 */
abstract class PartialMatcher implements PartialMatcherInterface {

  /**
   * The RouteCollection this matcher should match against.
   *
   * @var RouteCollection
   */
  protected $routes;

  /**
   * Sets the route collection this matcher should use.
   *
   * @param RouteCollection $collection
   *   The collection against which to match.
   *
   * @return PartialMatcherInterface
   *   The current matcher.
   */
  public function setCollection(RouteCollection $collection) {
    $this->routes = $collection;

    return $this;
  }

}
