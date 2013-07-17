<?php

/**
 * @file
 * Contains \Drupal\menu\Access\DeleteMenuAccessCheck.
 */

namespace Drupal\menu\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for menu delete routes.
 */
class DeleteMenuAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_menu_delete_menu');
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
