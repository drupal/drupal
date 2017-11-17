<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhances routes to ensure the entity is available with a generic name.
 *
 * @internal
 */
class LayoutBuilderRouteEnhancer implements EnhancerInterface {

  /**
   * Returns whether the enhancer runs on the current route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   *
   * @return bool
   *   TRUE if this enhancer applies to this route.
   */
  protected function applies(Route $route) {
    return $route->getOption('_layout_builder') && $route->getDefault('entity_type_id');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$this->applies($route)) {
      return $defaults;
    }

    $defaults['is_rebuilding'] = (bool) $request->query->get('layout_is_rebuilding', FALSE);

    if (!isset($defaults[$defaults['entity_type_id']])) {
      throw new \RuntimeException(sprintf('Failed to find the "%s" entity in route named %s', $defaults['entity_type_id'], $defaults[RouteObjectInterface::ROUTE_NAME]));
    }

    // Copy the entity by reference so that any changes are reflected.
    $defaults['entity'] = &$defaults[$defaults['entity_type_id']];
    return $defaults;
  }

}
