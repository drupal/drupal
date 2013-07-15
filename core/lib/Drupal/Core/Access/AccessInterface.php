<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessInterface.
 */

namespace Drupal\Core\Access;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * An access check service determines access rules for particular routes.
 */
interface AccessInterface {

  /**
   * Grant access.
   *
   * A checker should return this value to indicate that it grants access to a
   * route.
   */
  const ALLOW = TRUE;

  /**
   * Deny access.
   *
   * A checker should return this value to indicate it does not grant access to
   * a route.
   */
  const DENY = NULL;

  /**
   * Block access.
   *
   * A checker should return this value to indicate that it wants to completely
   * block access to this route, regardless of any other access checkers. Most
   * checkers should prefer DENY.
   */
  const KILL = FALSE;

  /**
   * Checks for access to a route.
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
