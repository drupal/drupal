<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuTreeInterface.
 */

namespace Drupal\menu_link;

/**
 * Defines an interface for trees out of menu links.
 */
interface MenuTreeInterface {

  /**
   * Returns a rendered menu tree.
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
  public function renderTree($tree);

  /**
   * Sets the path for determining the active trail of the specified menu tree.
   *
   * This path will also affect the breadcrumbs under some circumstances.
   * Breadcrumbs are built using the preferred link returned by
   * menu_link_get_preferred(). If the preferred link is inside one of the menus
   * specified in calls to static::setPath(), the preferred link will be
   * overridden by the corresponding path returned by static::getPath().
   *
   * @param string $menu_name
   *   The name of the affected menu tree.
   * @param string $path
   *   The path to use when finding the active trail.
   */
  public function setPath($menu_name, $path = NULL);

  /**
   * Gets the path for determining the active trail of the specified menu tree.
   *
   * @param string $menu_name
   *   The menu name of the requested tree.
   *
   * @return string
   *   A string containing the path. If no path has been specified with
   *   static::setPath(), NULL is returned.
   */
  public function getPath($menu_name);

  /**
   * Gets the active trail IDs of the specified menu tree.
   *
   * @param string $menu_name
   *   The menu name of the requested tree.
   *
   * @return array
   *   An array containing the active trail: a list of mlids.
   */
  public function getActiveTrailIds($menu_name);

  /**
   * Sorts and returns the built data representing a menu tree.
   *
   * @param array $links
   *   A flat array of menu links that are part of the menu. Each array element
   *   is an associative array of information about the menu link, containing
   *   the fields from the {menu_links} table, and optionally additional
   *   information from the {menu_router} table, if the menu item appears in
   *   both tables. This array must be ordered depth-first.
   *   See _menu_build_tree() for a sample query.
   * @param array $parents
   *   An array of the menu link ID values that are in the path from the current
   *   page to the root of the menu tree.
   * @param int $depth
   *   The minimum depth to include in the returned menu tree.
   *
   * @return array
   *   An array of menu links in the form of a tree. Each item in the tree is an
   *   associative array containing:
   *   - link: The menu link item from $links, with additional element
   *     'in_active_trail' (TRUE if the link ID was in $parents).
   *   - below: An array containing the sub-tree of this item, where each
   *     element is a tree item array with 'link' and 'below' elements. This
   *     array will be empty if the menu item has no items in its sub-tree
   *     having a depth greater than or equal to $depth.
   */
  public function buildTreeData(array $links, array $parents = array(), $depth = 1);

  /**
   * Gets the data structure for a named menu tree, based on the current page.
   *
   * The tree order is maintained by storing each parent in an individual
   * field, see http://drupal.org/node/141866 for more.
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
   *   is a list of associative arrays -- these have two keys, link and below.
   *   link is a menu item, ready for theming as a link. Below represents the
   *   submenu below the link if there is one, and it is a subtree that has the
   *   same structure described for the top-level array.
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
   * @param array $link
   *   A fully loaded menu link, or NULL. If a link is supplied, only the
   *   path to root will be included in the returned tree - as if this link
   *   represented the current page in a visible menu.
   * @param int $max_depth
   *   Optional maximum depth of links to retrieve. Typically useful if only one
   *   or two levels of a sub tree are needed in conjunction with a non-NULL
   *   $link, in which case $max_depth should be greater than $link['depth'].
   *
   * @return array
   *   An tree of menu links in an array, in the order they should be rendered.
   */
  public function buildAllData($menu_name, $link = NULL, $max_depth = NULL);

  /**
   * Renders a menu tree based on the current path.
   *
   * @param string $menu_name
   *   The name of the menu.
   *
   * @return array
   *   A structured array representing the specified menu on the current page,
   *   to be rendered by drupal_render().
   */
  public function renderMenu($menu_name);

  /**
   * Builds a menu tree, translates links, and checks access.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param array $parameters
   *   (optional) An associative array of build parameters. Possible keys:
   *   - expanded: An array of parent link ids to return only menu links that
   *     are children of one of the plids in this list. If empty, the whole menu
   *     tree is built, unless 'only_active_trail' is TRUE.
   *   - active_trail: An array of mlids, representing the coordinates of the
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
   *   A fully built menu tree.
   */
  public function buildTree($menu_name, array $parameters = array());

}
