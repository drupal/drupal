<?php

/**
 * @file
 * Contains \Drupal\menu\Access\DeleteLinkAccessCheck.
 */

namespace Drupal\menu\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for menu link delete routes.
 */
class DeleteLinkAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_menu_delete_link', $route->getRequirements());
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
