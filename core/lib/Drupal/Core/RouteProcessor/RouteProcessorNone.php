<?php

/**
 * @file
 * Contains \Drupal\Core\RouteProcessor\RouteProcessorNone.
 */

namespace Drupal\Core\RouteProcessor;

use Symfony\Component\Routing\Route;

/**
 * Provides a route processor to replace <none>.
 */
class RouteProcessorNone implements OutboundRouteProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters) {
    if ($route_name === '<none>') {
      $route->setPath('');
    }
  }

}
