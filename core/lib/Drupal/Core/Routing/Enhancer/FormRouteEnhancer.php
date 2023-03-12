<?php

namespace Drupal\Core\Routing\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhancer to add a wrapping controller for _form routes.
 */
class FormRouteEnhancer implements EnhancerInterface {

  /**
   * Returns whether the enhancer runs on the current route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   *
   * @return bool
   */
  protected function applies(Route $route) {
    return $route->hasDefault('_form') && !$route->hasDefault('_controller');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$this->applies($route)) {
      return $defaults;
    }

    $defaults['_controller'] = 'controller.form:getContentResult';
    return $defaults;
  }

}
