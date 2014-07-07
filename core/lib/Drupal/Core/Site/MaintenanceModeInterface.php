<?php

/**
 * @file
 * Contains \Drupal\Core\Site\MaintenanceModeInterface.
 */

namespace Drupal\Core\Site;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface for the maintenance mode service.
 */
interface MaintenanceModeInterface {

  /**
   * Returns whether the site is in maintenance mode.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return bool
   *   TRUE if the site is in maintenance mode.
   */
  public function applies(RouteMatchInterface $route_match);

  /**
   * Determines whether a user has access to the site in maintenance mode.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The logged in user.
   *
   * @return bool
   *   TRUE if the user should be exempted from maintenance mode.
   */
  public function exempt(AccountInterface $account);

}
