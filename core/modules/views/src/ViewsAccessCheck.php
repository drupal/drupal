<?php

/**
 * @file
 * Contains \Drupal\views\ViewsAccessCheck.
 */

namespace Drupal\views;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route access checker for the _access_all_views permission.
 *
 * @todo We could leverage the permission one as well?
 */
class ViewsAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasDefault('view_id');
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access all views');
  }

}
