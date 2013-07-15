<?php

/**
 * @file
 * Contains \Drupal\views\ViewsAccessCheck.
 */

namespace Drupal\views;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines a route access checker for the _access_all_views permission.
 *
 * @todo We could leverage the permission one as well?
 */
class ViewsAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('views_id');
  }

  /**
   * Implements AccessCheckInterface::applies().
   */
  public function access(Route $route, Request $request) {
    $access = user_access('access all views');

    return $access ?: NULL;
  }

}
