<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Access\AccessInterface.
 */

namespace Drupal\Core\Routing\Access;

use Drupal\Core\Access\AccessInterface as GenericAccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * An access check service determines access rules for particular routes.
 */
interface AccessInterface extends GenericAccessInterface {

  /**
   * Checks for access to a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return bool|null
   *   self::ALLOW, self::DENY, or self::KILL.
   */
  public function access(Route $route, Request $request, AccountInterface $account);

}
