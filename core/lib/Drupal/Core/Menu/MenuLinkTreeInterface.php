<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkTreeInterface.
 */

namespace Drupal\Core\Menu;

/**
 * Defines an interface for retrieving menu link trees.
 */
interface MenuLinkTreeInterface {

  /**
   * The maximum depth of tree that is supported.
   *
   * @return int
   *   The maximum depth.
   */
  public function maxDepth();

  /**
   * Returns a menu tree ready to be rendered.
   *
   * The menu item's LI element is given one of the following classes:
   * - expanded: The menu item is showing its submenu.
   * - collapsed: The menu item has a submenu which is not shown.
   * - leaf: The menu item has no submenu.
   *
   * @param array $tree
   *   A data structure representing the tree as returned from menu_tree_data.
   *
   * @return array
   *   A structured array to be rendered by drupal_render().
   */
  public function buildRenderTree($tree);

  /**
   * Gets the active trail IDs of the specified menu tree.
   *
   * @param string $menu_name
   *   The menu name of the requested tree.
   *
   * @return array
   *   An array containing the active trail: a list of plugin ids.
   */
  public function getActiveTrailIds($menu_name);

  /**
   * Gets the active menus for the current page.
   *
   * The active menu for the page determines the active trail.
   *
   * @return array
   *   An array of menu machine names, in order of preference. The
   *   'system.menu:active_menus_default' config item may be used to set a menu
   *   order different from the default order, or to prevent a particular menu
   *   from being used at all in the active trail.
   */
  public function getActiveMenuNames();

  /**
   * Sets the active menu for the current page.
   *
   * This overrides for the current page the preferred list of menus returned
   * by getActiveMenuNames(). The active menu for the page determines the active
   * trail.
   *
   * @param array $menu_names
   *   The menu names to use as active for the current page.
   */
  public function setActiveMenuNames(array $menu_names);

  /**
   * Gets the data structure for a named menu tree, based on the current page.
   *
   * Only visible links (hidden == 0) are returned in the data.
   *
   * @param string $menu_name
   *   The named menu links to return.
   * @param int $max_depth
   *   (optional) The maximum depth of links to retrieve.
   * @param bool $only_active_trail
   *   (optional) Whether to only return the links in the active trail (TRUE)
   *   instead of all links on every level of the menu link tree (FALSE).
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of menu links, in the order they should be rendered. The array
   *   is a list of associative arrays -- these have several keys:
   *   - link: the menu link plugin instance
   *   - below: the subtree below the link, or empty array. It has the same
   *            structure as the top level array.
   *   - depth:
   *   - has_children: boolean. even if the below value may be empty the link
   *                   may have children in the tree that are not shown. This
   *                   is a hint for adding appropriate classes for theming.
   *   - in_active_trail: boolean
   */
  public function buildPageData($menu_name, $max_depth = NULL, $only_active_trail = FALSE);

  /**
   * Gets the data structure representing a named menu tree.
   *
   * Since this can be the full tree including hidden items, the data returned
   * may be used for generating an an admin interface or a select.
   *
   * @param string $menu_name
   *   The named menu links to return
   * @param array $id
   *   A menu link ID, or NULL. If a link ID is supplied, only the
   *   path to root will be included in the returned tree - as if this link
   *   represented the current page in a visible menu.
   * @param int $max_depth
   *   Optional maximum depth of links to retrieve. Typically useful if only one
   *   or two levels of a sub tree are needed in conjunction with a non-NULL
   *   $id, in which case $max_depth should be greater than $link['depth'].
   *
   * @return array
   *   An tree of menu links in an array, in the order they should be rendered.
   */
  public function buildAllData($menu_name, $id = NULL, $max_depth = NULL);

  /**
   * Builds a menu tree, translates links, and checks access.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param array $parameters
   *   (optional) An associative array of build parameters. Possible keys:
   *   - expanded: An array of parent link ids to return only menu links that
   *     are children of one of the ids in this list. If empty, the whole menu
   *     tree is built, unless 'only_active_trail' is TRUE.
   *   - active_trail: An array of ids, representing the coordinates of the
   *     currently active menu link.
   *   - only_active_trail: Whether to only return links that are in the active
   *     trail. This option is ignored, if 'expanded' is non-empty.
   *   - min_depth: The minimum depth of menu links in the resulting tree.
   *     Defaults to 1, which is the default to build a whole tree for a menu
   *     (excluding menu container itself).
   *   - max_depth: The maximum depth of menu links in the resulting tree.
   *   - conditions: An associative array of custom database select query
   *     condition key/value pairs; see _menu_build_tree() for the actual query.
   *
   * @return array
   *   A fully built and access-checked menu tree.
   */
  public function buildTree($menu_name, array $parameters = array());

  /**
   * Returns a subtree starting with the passed in menu link plugin ID.
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param int $max_relative_depth
   *   The maximum depth of child menu links relative to the passed in.
   *
   * @return array
   *   A fully built and access-checked menu subtree.
   */
  public function buildSubtree($id, $max_relative_depth = NULL);

  /**
   * Loads all child links of a given menu link.
   *
   * @param string $id
   *   The menu link plugin ID.
   *
   * @param int $max_relative_depth
   *   If provided, limit the maximum relative depth of children retrieved.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   *   An array of child links keyed by ID.
   */
  public function getChildLinks($id, $max_relative_depth = NULL);

  /**
   * Fetches a menu link which matches the route name, parameters and menu name.
   *
   * @param string $route_name
   *   (optional) The route name, defaults to NULL.
   * @param array $route_parameters
   *   (optional) the route parameters, defaults to an empty array.
   * @param string|NULL $selected_menu
   *   (optional) The menu to use to find preferred links, defaults to NULL.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   The prepared menu link for the given route name, parameters and menu.
   */
  public function menuLinkGetPreferred($route_name = NULL, array $route_parameters = array(), $selected_menu = NULL);

  /**
   * Gets the options for a select element to choose a menu and parent.
   *
   * @param string $id
   *   Optional ID of a link plugin. This will exclude the link and its
   *   children from the select options.
   * @param array $menus
   *   Optional array of menu names as keys and titles as values to limit
   *   the select options.  If NULL, all menus will be included.
   *
   * @return array
   *   Keyed array where the keys are contain a menu name and parent ID and
   *   the values are a menu name or link title indented by depth.
   */
  public function getParentSelectOptions($id = '', array $menus = NULL);

  /**
   * Get a form element to choose a menu and parent.
   *
   * The specific type of form element will vary depending on the
   * implementation, but callers will normally need to set the #title for the
   * element.
   *
   * @param string $menu_parent
   *   A menu name and parent ID concatenated with a ':' character to use as the
   *   default value.
   * @param string $id
   *   Optional ID of a link plugin. This will exclude the link and its
   *   children from being selected.
   * @param array $menus
   *   Optional array of menu names as keys and titles as values to limit
   *   the values that may be selected. If NULL, all menus will be included.
   *
   * @return array
   *   A form element to choose a parent, or an empty array if no possible
   *   parents exist for the given parameters. The resulting form value will be
   *   a single string containing the chosen menu name and parent ID separated
   *   by a ':' character.
   */
  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL);

  /**
   * Gets a list of menu names for use as options.
   *
   * @param array $menu_names
   *   Optional array of menu names to limit the options, or NULL to load all.
   *
   * @return array
   *   Keys are menu names (ids) values are the menu labels.
   */
  public function getMenuOptions(array $menu_names = NULL);

  /**
   * Returns the maximum depth of the possible parents of the menu link.
   *
   * @param string $id
   *   The menu link plugin ID or an empty value for a new link.
   *
   * @return int
   *   The depth related to the depth of the given menu link.
   */
  public function getParentDepthLimit($id);

  /**
   * For test purposes, clear any static data caches.
   */
  public function resetStaticCache();

}
