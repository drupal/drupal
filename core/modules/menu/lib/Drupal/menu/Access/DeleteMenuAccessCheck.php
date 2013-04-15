<?php

/**
 * @file
 * Contains \Drupal\menu\Access\DeleteMenuAccessCheck.
 */

namespace Drupal\menu\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for menu delete routes.
 */
class DeleteMenuAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_menu_delete_menu', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if (user_access('administer menu') && $menu = $request->attributes->get('menu')) {
      // System-defined menus may not be deleted.
      $system_menus = menu_list_system_menus();
      return !isset($system_menus[$menu->id()]);
    }
    return FALSE;
  }
}
