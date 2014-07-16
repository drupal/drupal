<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuActiveTrailInterface.
 */

namespace Drupal\Core\Menu;

/**
 * Defines an interface for the active menu trail service.
 *
 * The active trail of a given menu is the trail from the current page to the
 * root of that menu's tree.
 */
interface MenuActiveTrailInterface {

  /**
   * Gets the active trail IDs of the specified menu tree.
   *
   * @param string $menu_name
   *   The menu name of the requested tree.
   *
   * @return array
   *   An array containing the active trail: a list of plugin IDs.
   */
  public function getActiveTrailIds($menu_name);

  /**
   * Gets the active trail cache key of the specified menu tree.
   *
   * @param string $menu_name
   *   The menu name of the requested tree.
   *
   * @return string
   *   The cache key that uniquely identifies the active trail of the menu tree.
   */
  public function getActiveTrailCacheKey($menu_name);

  /**
   * Fetches a menu link which matches the route name, parameters and menu name.
   *
   * @param string|NULL $menu_name
   *   (optional) The menu within which to find the active link. If omitted, all
   *   menus will be searched.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|NULL
   *   The menu link for the given route name, parameters and menu, or NULL if
   *   there is no matching menu link or the current user cannot access the
   *   current page (i.e. we have a 403 response).
   */
  public function getActiveLink($menu_name = NULL);

}
