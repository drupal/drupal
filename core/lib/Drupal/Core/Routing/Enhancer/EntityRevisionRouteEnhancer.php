<?php

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Adds _entity_revision to the request attributes, if possible.
 */
class EntityRevisionRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    // Check whether there is any entity revision parameter.
    $parameters = $route->getOption('parameters') ?: [];
    foreach ($parameters as $info) {
      if (isset($info['type']) && strpos($info['type'], 'entity_revision:') === 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    $options = $route->getOptions();
    if (isset($options['parameters'])) {
      foreach ($options['parameters'] as $name => $details) {
        if (!empty($details['type']) && strpos($details['type'], 'entity_revision:') !== FALSE) {
          $defaults['_entity_revision'] = $defaults[$name];
          break;
        }
      }
    }

    return $defaults;
  }

}
