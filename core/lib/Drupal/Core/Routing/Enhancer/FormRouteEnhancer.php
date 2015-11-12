<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\FormRouteEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhancer to add a wrapping controller for _form routes.
 */
class FormRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasDefault('_form') && !$route->hasDefault('_controller');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $defaults['_controller'] = 'controller.form:getContentResult';
    return $defaults;
  }

}
