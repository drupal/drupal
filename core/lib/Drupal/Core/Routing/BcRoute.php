<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Route;

/**
 * A backwards compatibility route.
 *
 * When a route is deprecated for another one, and backwards compatibility is
 * provided, then it's best practice to:
 * - not duplicate all route definition metadata, to instead have an "as empty
 *   as possible" route
 * - have an accompanying outbound route processor, that overwrites this empty
 *   route definition with the redirected route's definition.
 *
 * @see \Drupal\rest\RouteProcessor\RestResourceGetRouteProcessorBC
 */
class BcRoute extends Route {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct('');
    $this->setOption('bc_route', TRUE);
  }

}
