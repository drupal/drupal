<?php

/**
 * @file
 * Contains \Drupal\menu\Access\DeleteLinkAccessCheck.
 */

namespace Drupal\menu\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for menu link delete routes.
 */
class DeleteLinkAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_menu_delete_link');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if (user_access('administer menu') && $menu_link = $request->attributes->get('menu_link')) {
      // Links defined via hook_menu may not be deleted. Updated items are an
      // exception, as they can be broken.
      return $menu_link->module !== 'system' || $menu_link->updated;
    }
    return FALSE;
  }
}
