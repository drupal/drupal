<?php

namespace Drupal\Core\Menu;

/**
 * Defines an interface for storing a menu tree containing menu link IDs.
 *
 * The tree contains a hierarchy of menu links which have an ID as well as a
 * route name or external URL.
 */
interface MenuTreeStorageInterface {

  /**
   * The maximum depth of tree the storage implementation supports.
   *
   * @return int
   *   The maximum depth.
   */
  public function maxDepth();

  /**
   * Clears all definitions cached in memory.
   */
  public function resetDefinitions();

  /**
   * Rebuilds the stored menu link definitions.
   *
   * Links that saved by passing definitions into this method must be included
   * on all future calls, or they will be purged. This allows for automatic
   * cleanup e.g. when modules are uninstalled.
   *
   * @param array $definitions
   *   The new menu link definitions.
   */
  public function rebuild(array $definitions);

  /**
   * Loads a menu link plugin definition from the storage.
   *
   * @param string $id
   *   The menu link plugin ID.
   *
   * @return array|false
   *   The plugin definition, or FALSE if no definition was found for the ID.
   */
  public function load($id);

  /**
   * Loads multiple plugin definitions from the storage.
   *
   * @param array $ids
   *   An array of plugin IDs.
   *
   * @return array
   *   An array of plugin definition arrays keyed by plugin ID, which are the
   *   actual definitions after the loadMultiple() including all those plugins
   *   from $ids.
   */
  public function loadMultiple(array $ids);

  /**
   * Loads multiple plugin definitions from the storage based on properties.
   *
   * @param array $properties
   *   The properties to filter by.
   *
   * @return array
   *   An array of menu link definition arrays.
   *
   * @throws \InvalidArgumentException
   *   Thrown if an invalid property name is specified in $properties.
   */
  public function loadByProperties(array $properties);

  /**
   * Loads multiple plugin definitions from the storage based on route.
   *
   * @param string $route_name
   *   The route name.
   * @param array $route_parameters
   *   (optional) The route parameters. Defaults to an empty array.
   * @param string $menu_name
   *   (optional) Restricts the found links to just those in the named menu.
   *
   * @return array
   *   An array of menu link definitions keyed by ID and ordered by depth.
   */
  public function loadByRoute($route_name, array $route_parameters = [], $menu_name = NULL);

  /**
   * Saves a plugin definition to the storage.
   *
   * @param array $definition
   *   A definition for a \Drupal\Core\Menu\MenuLinkInterface plugin.
   *
   * @return array
   *   The menu names affected by the save operation. This will be one menu
   *   name if the link is saved to the sane menu, or two if it is saved to a
   *   new menu.
   *
   * @throws \Exception
   *   Thrown if the storage back-end does not exist and could not be created.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown if the definition is invalid - for example, if the specified
   *   parent would cause the links children to be moved to greater than the
   *   maximum depth.
   */
  public function save(array $definition);

  /**
   * Deletes a menu link definition from the storage.
   *
   * @param string $id
   *   The menu link plugin ID.
   */
  public function delete($id);

  /**
   * Loads a menu link tree from the storage.
   *
   * This function may be used build the data for a menu tree only, for example
   * to further massage the data manually before further processing happens.
   * MenuLinkTree::checkAccess() needs to be invoked afterwards.
   *
   * The tree order is maintained using an optimized algorithm, for example by
   * storing each parent in an individual field, see
   * https://www.drupal.org/node/141866 for more details. However, any details
   * of the storage should not be relied upon since it may be swapped with a
   * different implementation.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param \Drupal\Core\Menu\MenuTreeParameters $parameters
   *   The parameters to determine which menu links to be loaded into a tree.
   *
   * @return array
   *   An array with 2 elements:
   *   - tree: A fully built menu tree containing an array.
   *     @see static::treeDataRecursive()
   *   - route_names: An array of all route names used in the tree.
   */
  public function loadTreeData($menu_name, MenuTreeParameters $parameters);

  /**
   * Loads all the enabled menu links that are below the given ID.
   *
   * The returned links are not ordered, and visible children will be included
   * even if they have parent that is not enabled or ancestor so would not
   * normally appear in a rendered tree.
   *
   * @param string $id
   *   The parent menu link ID.
   * @param int $max_relative_depth
   *   The maximum relative depth of the children relative to the passed parent.
   *
   * @return array
   *   An array of enabled link definitions, keyed by ID.
   */
  public function loadAllChildren($id, $max_relative_depth = NULL);

  /**
   * Loads all the IDs for menu links that are below the given ID.
   *
   * @param string $id
   *   The parent menu link ID.
   *
   * @return array
   *   An unordered array of plugin IDs corresponding to all children.
   */
  public function getAllChildIds($id);

  /**
   * Loads a subtree rooted by the given ID.
   *
   * The returned links are structured like those from loadTreeData().
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param int $max_relative_depth
   *   (optional) The maximum depth of child menu links relative to the passed
   *   in. Defaults to NULL, in which case the full subtree will be returned.
   *
   * @return array
   *   An array with 2 elements:
   *   - subtree: A fully built menu tree element or FALSE.
   *   - route_names: An array of all route names used in the subtree.
   */
  public function loadSubtreeData($id, $max_relative_depth = NULL);

  /**
   * Returns all the IDs that represent the path to the root of the tree.
   *
   * @param string $id
   *   A menu link ID.
   *
   * @return array
   *   An associative array of IDs with keys equal to values that represents the
   *   path from the given ID to the root of the tree. If $id is an ID that
   *   exists, the returned array will at least include it.  An empty array is
   *   returned if the ID does not exist in the storage. An example $id (8) with
   *   two parents (1, 6) looks like the following:
   * @code
   *   array(
   *     'p1' => 1,
   *     'p2' => 6,
   *     'p3' => 8,
   *     'p4' => 0,
   *     'p5' => 0,
   *     'p6' => 0,
   *     'p7' => 0,
   *     'p8' => 0,
   *     'p9' => 0
   *   )
   * @endcode
   */
  public function getRootPathIds($id);

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

  /**
   * Finds the height of a subtree rooted by the given ID.
   *
   * @param string $id
   *   The ID of an item in the storage.
   *
   * @return int
   *   Returns the height of the subtree. This will be at least 1 if the ID
   *   exists, or 0 if the ID does not exist in the storage.
   */
  public function getSubtreeHeight($id);

  /**
   * Determines whether a specific menu name is used in the tree.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return bool
   *   Returns TRUE if the given menu name is used, otherwise FALSE.
   */
  public function menuNameInUse($menu_name);

  /**
   * Returns the used menu names in the tree storage.
   *
   * @return array
   *   The menu names.
   */
  public function getMenuNames();

  /**
   * Counts the total number of menu links in one menu or all menus.
   *
   * @param string $menu_name
   *   (optional) The menu name to count by. Defaults to all menus.
   *
   * @return int
   *   The number of menu links in the named menu, or in all menus if the menu
   *   name is NULL.
   */
  public function countMenuLinks($menu_name = NULL);

}
