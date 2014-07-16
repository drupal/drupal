<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkTreeInterface.
 */

namespace Drupal\Core\Menu;

/**
 * Defines an interface for loading, transforming and rendering menu link trees.
 *
 * The main goal of this service is to, given a menu name, load (::load()) the
 * corresponding tree of menu links and turning this list of menu links into a
 * tree (by looking at their tree metadata). Because menu links themselves are
 * responsible for translation, this will already be translated for the current
 * language.
 * Which links are loaded can be specified in the menu link tree parameters that
 * passed to ::load(). You can build your own set of parameter, but you can also
 * start from a typical default (::getCurrentRouteMenuTreeParameters()).
 *
 * @see \Drupal\Core\Menu\MenuLinkTreeParameters
 *
 * If desired, one can transform (::transform()) that tree of menu links, for
 * example performing access checking (to only show those links that can be
 * accessed by the end user) or adding custom classes to links (to show icons
 * next to the links). Very complex tasks can be performed as well (such as
 * extracting a subtree from the menu link tree depending on the active trail).
 * These transformations are performed by "menu link tree manipulators", and
 * they can be used to perform any kind of transformation imaginable.
 *
 * @see \Drupal\menu_link\DefaultMenuTreeManipulators
 *
 * Finally, if desired, that tree of menu links can be built into a renderable
 * array (::build()) for rendering as HTML.
 */
interface MenuLinkTreeInterface {

  /**
   * Gets the link tree parameters for rendering a specific menu.
   *
   * Builds menu link tree parameters that:
   * - expand all links in the active trail based on route being viewed
   * - also expands the descendants of the links in the active trail whose
   *   'expanded' flag is enabled
   *
   * This only sets the (relatively complex) parameters to achieve the two above
   * goals, but you can still further customize these parameters.
   *
   * @see \Drupal\Core\Menu\MenuLinkTreeParameters
   *
   * @param string $menu_name
   *   The menu name, needed for retrieving the active trail and links with the
   *   'expanded' flag enabled.
   *
   * @return \Drupal\Core\Menu\MenuTreeParameters
   *   The parameters to determine which menu links to be loaded into a tree.
   */
  public function getCurrentRouteMenuTreeParameters($menu_name);

  /**
   * Loads a menu tree with a menu link plugin instance at each element.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param \Drupal\Core\Menu\MenuTreeParameters $parameters
   *   The parameters to determine which menu links to be loaded into a tree.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   A menu link tree.
   */
  public function load($menu_name, MenuTreeParameters $parameters);

  /**
   * Applies menu link tree manipulators to transform the given tree.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu tree to manipulate.
   * @param array $manipulators
   *   The menu link tree manipulators to apply. Each is an array with keys:
   *   - callable: a callable or a string that can be resolved to a callable
   *               by \Drupal\Core\Controller\ControllerResolverInterface::getControllerFromDefinition()
   *   - args: optional array of arguments to pass to the callable after $tree.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function transform(array $tree, array $manipulators);

  /**
   * Builds a renderable array of a menu tree.
   *
   * The menu item's LI element is given one of the following classes:
   * - expanded: The menu item is showing its submenu.
   * - collapsed: The menu item has a submenu which is not shown.
   * - leaf: The menu item has no submenu.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   A data structure representing the tree as returned from ::load().
   *
   * @return array
   *   A renderable array.
   */
  public function build(array $tree);

  /**
   * Returns the maximum depth of tree that is supported.
   *
   * @return int
   *   The maximum depth.
   */
  public function maxDepth();

  /**
   * Finds the height of a subtree rooted by of the given ID.
   *
   * @param string $id
   *   The the ID of an item in the storage.
   *
   * @return int
   *   Returns the height of the subtree. This will be at least 1 if the ID
   *   exists, or 0 if the ID does not exist in the storage.
   */
  public function getSubtreeHeight($id);

  /**
   * Finds expanded links in a menu given a set of possible parents.
   *
   * @param string $menu_name
   *   The menu name.
   * @param array $parents
   *   One or more parent IDs to match.
   *
   * @return array
   *   The menu link IDs that are flagged as expanded in this menu.
   */
  public function getExpanded($menu_name, array $parents);

}
