<?php

/**
 * @file
 * Contains Drupal\Core\Access\AccessCheckInterface.
 */

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * An access check service determines access rules for particular routes.
 */
interface AccessCheckInterface {

  /**
   * Declares whether the access check applies to a specific route or not.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to consider attaching to.
   *
   * @return bool
   *   TRUE if the check applies to the passed route, FALSE otherwise.
   */
  public function applies(Route $route);

  /**
   * Checks for access to route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return mixed
   *   TRUE if access is allowed.
   *   FALSE if not.
   *   NULL if no opinion.
   */
  public function access(Route $route, Request $request);
}
