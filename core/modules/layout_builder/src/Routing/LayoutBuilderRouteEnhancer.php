<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Converts the query parameter for layout rebuild status to a route default.
 *
 * @internal
 */
class LayoutBuilderRouteEnhancer implements EnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if ($route->hasOption('_layout_builder')) {
      $defaults['is_rebuilding'] = (bool) $request->query->get('layout_is_rebuilding', FALSE);
    }
    return $defaults;
  }

}
