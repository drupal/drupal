<?php

namespace Drupal\dynamic_page_cache\PageCache\ResponsePolicy;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache policy for routes with the '_admin_route' option set.
 *
 * This policy rule denies caching of responses generated for admin routes,
 * because admin routes have very low cache hit ratios due to low traffic and
 * form submissions.
 */
class DenyAdminRoutes implements ResponsePolicyInterface {

  /**
   * Constructs a deny admin route page cache policy.
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
    if (($route = $this->routeMatch->getRouteObject()) && $route->getOption('_admin_route')) {
      return static::DENY;
    }
  }

}
