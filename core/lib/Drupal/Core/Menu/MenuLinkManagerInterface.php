<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkTreeInterface.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for creating menu links and retrieving menu link trees.
 */
interface MenuLinkManagerInterface extends PluginManagerInterface {

  /**
   * Trigger discovery, save, and cleanup of static links.
   */
  public function rebuild();

  /**
   * Deletes or resets all links for a menu.
   *
   * @param string $menu_name
   *   The name of the menu whose links will be deleted or reset.
   */
  public function deleteLinksInMenu($menu_name);

  /**
   * Deletes a single link from the menu tree.
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param bool $persist
   *   If TRUE, this method will attempt to persist the deletion from any
   *   external storage by invoking MenuLinkInterface::deleteLink() on
   *   the plugin that is being deleted.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the $id is not valid, existing, plugin ID or if the link cannot be
   *   deleted.
   */
  public function deleteLink($id, $persist = TRUE);

  /**
   * Load multiple plugin instances based on route.
   *
   * @param string $route_name
   *   The route name.
   * @param array $route_parameters
   *   (optional) The route parameters, defaults to an empty array.
   * @param bool $include_hidden
   *   (optional) Flag to specify whether hidden links should be returned too.
   *   Defaults to FALSE.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   *   An array of instances keyed by ID.
   */
  public function loadLinksByRoute($route_name, array $route_parameters = array(), $include_hidden = FALSE);

  /**
   * Adds a new link to the tree storage.
   *
   * Use this function in case you know there is no entry in the tree. This is
   * the case if you don't use plugin definition to fill in the tree.
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param array $definition
   *   The values of the link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   The updated menu link instance.
   */
  public function createLink($id, array $definition);

  /**
   * Updates the values for a menu link in the tree storage.
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param array $new_definition_values
   *   The new values for the link definition. This will usually be just a
   *   subset of the plugin definition.
   * @param bool $persist
   *   TRUE to also have the link instance itself persist the changed values
   *   to any additional storage by invoking MenuLinkInterface::updateLink() on
   *   the plugin that is being updated.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   The updated menu link instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the $id is not valid, existing, plugin ID.
   */
  public function updateLink($id, array $new_definition_values, $persist = TRUE);

  /**
   * Resets the values for a menu link based on the values found by discovery.
   *
   * @param string $id
   *   The menu link plugin ID.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   The menu link instance after being reset.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the $id is not valid, existing, plugin ID or if the link cannot be
   *   reset.
   */
  public function resetLink($id);

  /**
   * Counts the total number of menu links.
   *
   * @param string $menu_name
   *   (optional) The menu name to count by, defaults to NULL.
   */
  public function countMenuLinks($menu_name = NULL);

  /**
   * Loads all parent link IDs of a given menu link.
   *
   * This method is very similar to getActiveTrailIds() but allows the link
   * to be specified rather than being discovered based on the menu name
   * and request. This method is mostly useful for testing.
   *
   * @param string $id
   *   The menu link plugin ID.
   *
   * @return array
   *   An ordered array of IDs representing the path to the root of the tree.
   *   The first element of the array will be equal to $id, unless $id is not
   *   valid, in which case the return value will be NULL.
   */
  public function getParentIds($id);

  /**
   * Loads all child link IDs of a given menu link, regardless of visibility.
   *
   * This method is mostly useful for testing.
   *
   * @param string $id
   *   The menu link plugin ID.
   *
   * @return array
   *   An unordered array of IDs representing the IDs of all children, or NULL
   *   if the ID is invalid.
   */
  public function getChildIds($id);

  /**
   * Determine if any links use a given menu name.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return bool
   *   TRUE if any links are present in the named menu, FALSE otherwise.
   */
  public function menuNameInUse($menu_name);

  /**
   * Resets any local definition cache. Used for testing.
   */
  public function resetDefinitions();

}
