<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\NullRouteMatch.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Stub implementation of RouteMatchInterface for when there's no matched route.
 */
class NullRouteMatch implements RouteMatchInterface {

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteObject() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameter($parameter_name) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return new ParameterBag();
  }

  /**
   * {@inheritdoc}
   */
  public function getRawParameter($parameter_name) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawParameters() {
    return new ParameterBag();
  }

}
