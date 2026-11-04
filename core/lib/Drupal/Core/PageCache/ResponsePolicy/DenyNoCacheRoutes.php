<?php

namespace Drupal\Core\PageCache\ResponsePolicy;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache policy for routes with the 'no_cache' option set.
 *
 * This policy rule denies caching of responses generated for routes that have
 * the 'no_cache' option set to TRUE.
 */
class DenyNoCacheRoutes implements ResponsePolicyInterface {

  /**
   * Constructs a deny node preview page cache policy.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    if (($route = $this->routeMatch->getRouteObject()) && $route->getOption('no_cache')) {
      return static::DENY;
    }
  }

}
