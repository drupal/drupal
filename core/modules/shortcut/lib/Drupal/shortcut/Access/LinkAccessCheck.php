<?php

/**
 * @file
 * Contains Drupal\shortcut\Access\LinkAccessCheck.
 */

namespace Drupal\shortcut\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access check for shortcut link delete routes.
 */
class LinkAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_shortcut_link');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $menu_link = $request->attributes->get('menu_link');
    $set_name = str_replace('shortcut-', '', $menu_link['menu_name']);
    if ($shortcut_set = shortcut_set_load($set_name)) {
      return shortcut_set_edit_access($shortcut_set) ? static::ALLOW : static::DENY;
    }
    return static::DENY;
  }

}
