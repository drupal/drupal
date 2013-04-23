<?php

/**
 * @file
 * Contains Drupal\shortcut\Access\LinkDeleteAccessCheck.
 */

namespace Drupal\shortcut\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access check for shortcut link delete routes.
 */
class LinkDeleteAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_shortcut_link_delete', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $menu_link = $request->attributes->get('menu_link');
    $set_name = str_replace('shortcut-', '', $menu_link['menu_name']);
    if ($shortcut_set = shortcut_set_load($set_name)) {
      return shortcut_set_edit_access($shortcut_set);
    }
  }

}
