<?php

/**
 * @file
 * Contains Drupal\Core\Routing\CurrentRouteMatch.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default object for current_route_match service.
 */
class CurrentRouteMatch implements RouteMatchInterface, StackedRouteMatchInterface {

  /**
   * The related request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Internal cache of RouteMatch objects.
   *
   * @var \SplObjectStorage
   */
  protected $routeMatches;

  /**
   * Constructs a CurrentRouteMatch object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *  The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    $this->routeMatches = new \SplObjectStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->getCurrentRouteMatch()->getRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteObject() {
    return $this->getCurrentRouteMatch()->getRouteObject();
  }

  /**
   * {@inheritdoc}
   */
  public function getParameter($parameter_name) {
    return $this->getCurrentRouteMatch()->getParameter($parameter_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return $this->getCurrentRouteMatch()->getParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getRawParameter($parameter_name) {
    return $this->getCurrentRouteMatch()->getRawParameter($parameter_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getRawParameters() {
    return $this->getCurrentRouteMatch()->getRawParameters();
  }

  /**
   * Returns the route match for the current request.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The current route match object.
   */
  public function getCurrentRouteMatch() {
    return $this->getRouteMatch($this->requestStack->getCurrentRequest());
  }

  /**
   * Returns the route match for a passed in request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   A route match object created from the request.
   */
  protected function getRouteMatch(Request $request) {
    if (isset($this->routeMatches[$request])) {
      $route_match = $this->routeMatches[$request];
    }
    else {
      $route_match = RouteMatch::createFromRequest($request);

      // Since getRouteMatch() might be invoked both before and after routing
      // is completed, only statically cache the route match after there's a
      // matched route.
      if ($route_match->getRouteObject()) {
        $this->routeMatches[$request] = $route_match;
      }
    }
    return $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterRouteMatch() {
    return $this->getRouteMatch($this->requestStack->getMasterRequest());
  }

  /**
   * {@inheritdoc}
   */
  public function getParentRouteMatch() {
    return $this->getRouteMatch($this->requestStack->getParentRequest());
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteMatchFromRequest(Request $request) {
    return $this->getRouteMatch($request);
  }

}
