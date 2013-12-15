<?php

/**
 * @file
 * Contains Drupal\Core\Routing\UrlMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Cmf\Component\Routing\NestedMatcher\UrlMatcher as BaseUrlMatcher;

/**
 * Drupal-specific URL Matcher; handles the Drupal "system path" mapping.
 */
class UrlMatcher extends BaseUrlMatcher {

  /**
   * Constructs a new UrlMatcher.
   *
   * The parent class has a constructor we need to skip, so just override it
   * with a no-op.
   */
  public function __construct() {}

  public function finalMatch(RouteCollection $collection, Request $request) {
    $this->routes = $collection;
    $context = new RequestContext();
    $context->fromRequest($request);
    $this->setContext($context);
    return $this->match('/' . $request->attributes->get('_system_path'));
  }

  /**
   * Returns the route_name and route parameters matching a system path.
   *
   * @todo Find a better place for this method in
   *   https://drupal.org/node/2153891.
   *
   * @param string $link_path
   *   The link path to find a route name for.
   *
   * @return array
   *   Returns an array with both the route name and parameters, or an empty
   *   array if no route was matched.
   */
  public function findRouteNameParameters($link_path) {
    // Look up the route_name used for the given path.
    $request = Request::create('/' . $link_path);
    $request->attributes->set('_system_path', $link_path);
    try {
      $result = \Drupal::service('router')->matchRequest($request);
      $return = array();
      $return[] = isset($result['_route']) ? $result['_route'] : '';
      $return[] = $result['_raw_variables']->all();
      return $return;
    }
    catch (\Exception $e) {
      return array();
    }
  }

}
