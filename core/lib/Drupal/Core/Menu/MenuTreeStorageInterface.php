<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuTreeStorageInterface.
 */

namespace Drupal\Core\Menu;

interface MenuTreeStorageInterface {

  /**
   * The maximum depth of tree the storage implementation supports.
   *
   * @return int
   *   The maximum depth.
   */
  public function maxDepth();

  /**
   * Helper function for testing. Clears all definitions cached in memory.
   */
  public function resetDefinitions();

  /**
   * Rebuilds the stored menu link definitions.
   *
   * @param array $definitions
   *   The new menu link definitions.
   *
   * @todo give this a better name.
   */
  public function rebuild(array $definitions);

  /**
   * Loads a menu link plugin definition from the storage.
   *
   * @param string $id
   *   The menu link plugin ID.
   *
   * @return array|FALSE
   *   Menu Link definition
   */
  public function load($id);

  /**
   * Loads multiple plugin definitions from the storage.
   *
   * @param array $ids
   *   An array of plugin IDs.
   *
   * @return array
   *   An array of menu Link definitions.
   */
  public function loadMultiple(array $ids);

  /**
   * Loads multiple plugin definitions from the storage based on properties.
   *
   * @param array $properties
   *   The properties to filter by.
   *
   * @return array
   *   An array of menu link definitions.
   */
  public function loadByProperties(array $properties);

  /**
   * Loads multiple plugin definitions from the storage based on route.
   *
   * @param string $route_name
   *   The route name.
   * @param array $route_parameters
   *   (optional) The route parameters, defaults to an empty array.
   * @param bool $include_hidden
   *   (optional) Flag to specify whether hidden links should be returned too.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of menu link definitions keyed by ID.
   */
  public function loadByRoute($route_name, array $route_parameters = array(), $include_hidden = FALSE);

  /**
   * Saves a plugin definition to the storage.
   *
   * @param array $definition
   *   A definition for a \Drupal\Core\Menu\MenuLinkInterface plugin.
   *
   * @return array
   *   The names of the menus affected by the save operation (1 or 2).
   *
   * @throws \Exception
   *   If the storage back-end does not exist and could not be created.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the definition is invalid - for example, if the specified parent
   *   would cause the links children to be moved to greater than the maximum
   *   depth.
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
   * http://drupal.org/node/141866 for more details. However, any details
   * of the storage should not be relied upon since it may be swapped with
   * a different implementation.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param array $parameters
   *   (optional) An associative array of build parameters. Possible keys:
   *   - expanded: An array of parent plugin ids to return only menu links that
   *     are children of one of the ids in this list. If empty, the whole menu
   *     tree is built, unless 'only_active_trail' is TRUE.
   *   - active_trail: An array of ids, representing the coordinates of the
   *     currently active menu link.
   *   - only_active_trail: Whether to only return links that are in the active
   *     trail. This option is ignored if 'expanded' is non-empty.
   *   - min_depth: The minimum depth of menu links in the resulting tree.
   *     Defaults to 1, which is the default to build a whole tree for a menu
   *     (excluding menu container itself).
   *   - max_depth: The maximum depth of menu links in the resulting tree.
   *   - conditions: An associative array of custom condition key/value pairs
   *     to restrict the links loaded. Each key must be one of the keys
   *     in the plugin definition.
   *
   * @return array
   *   A fully built menu tree.
   */
  public function loadTree($menu_name, array $parameters = array());

  /**
   * Loads all the visible menu links that are below the given ID.
   *
   * The returned links are not ordered, and visible children will be
   * included even if they have a hidden parent or ancestor so would not
   * normally appear in a rendered tree.
   *
   * @param string $id
   *   The parent menu link ID.
   * @param int $max_relative_depth
   *   The maximum relative depth of the children relative to the passed parent.
   *
   * @return array
   *   An array of visible (not hidden) link definitions, keyed by ID.
   */
  public function loadAllChildLinks($id, $max_relative_depth = NULL);

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
   * Loads a subtree rooted by the given menu link plugin ID.
   *
   * The returned links are structured like those from loadTree().
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param int $max_relative_depth
   *   The maximum depth of child menu links relative to the passed in.
   *
   * @return array
   *   A fully built menu subtree.
   */
  public function loadSubtree($id, $max_relative_depth = NULL);

  /**
   * Returns all the IDs that represent the path to the root of the tree.
   *
   * @param string $id
   *   A menu link ID.
   *
   * @return array
   *   An associative array of IDs with keys equal to values that represents the
   *   path from the given ID  to the root of the tree. If $id is an ID that
   *   exists, the returned array will at least include it.  An empty array
   *   is returned if the ID does not exist in the storage.
   */
  public function getRootPathIds($id);

  /**
   * Find expanded links in a menu given a set of possible parents.
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
   * Determines whether a specific menu named is used in the tree.
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
   *   (optional) The menu name to count by, defaults to NULL.
   *
   * @return int
   *   The number of menu links.
   */
  public function countMenuLinks($menu_name = NULL);

}
