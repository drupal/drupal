<?php

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for managing menu links and storing their definitions.
 *
 * Menu link managers support both automatic plugin definition discovery and
 * manually maintaining plugin definitions.
 *
 * MenuLinkManagerInterface::updateDefinition() can be used to update a single
 * menu link's definition and pass this onto the menu storage without requiring
 * a full MenuLinkManagerInterface::rebuild().
 *
 * Implementations that do not use automatic discovery should call
 * MenuLinkManagerInterface::addDefinition() or
 * MenuLinkManagerInterface::removeDefinition() when they add or remove links,
 * and MenuLinkManagerInterface::updateDefinition() to update links they have
 * already defined.
 */
interface MenuLinkManagerInterface extends PluginManagerInterface {

  /**
   * Triggers discovery, save, and cleanup of discovered links.
   */
  public function rebuild();

  /**
   * Deletes all links having a certain menu name.
   *
   * If a link is not deletable but is resettable, the link will be reset to have
   * its original menu name, under the assumption that the original menu is not
   * the one we are deleting it from. Note that when resetting, if the original
   * menu name is the same as the menu name passed to this method, the link will
   * not be moved or deleted.
   *
   * @param string $menu_name
   *   The name of the menu whose links will be deleted or reset.
   */
  public function deleteLinksInMenu($menu_name);

  /**
   * Removes a single link definition from the menu tree storage.
   *
   * This is used for plugins not found through discovery to remove definitions.
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param bool $persist
   *   If TRUE, this method will attempt to persist the deletion from any
   *   external storage by invoking MenuLinkInterface::deleteLink() on the
   *   plugin that is being deleted.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown if the $id is not a valid, existing, plugin ID or if the link
   *   cannot be deleted.
   */
  public function removeDefinition($id, $persist = TRUE);

  /**
   * Loads multiple plugin instances based on route.
   *
   * @param string $route_name
   *   The route name.
   * @param array $route_parameters
   *   (optional) The route parameters. Defaults to an empty array.
   * @param string $menu_name
   *   (optional) Restricts the found links to just those in the named menu.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   *   An array of instances keyed by plugin ID.
   */
  public function loadLinksByRoute($route_name, array $route_parameters = array(), $menu_name = NULL);

  /**
   * Adds a new menu link definition to the menu tree storage.
   *
   * Use this function when you know there is no entry in the tree. This is
   * used for plugins not found through discovery to add new definitions.
   *
   * @param string $id
   *   The plugin ID for the new menu link definition that is being added.
   * @param array $definition
   *   The values of the link definition.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   A plugin instance created using the newly added definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when the $id is not valid or is an already existing plugin ID.
   */
  public function addDefinition($id, array $definition);

  /**
   * Updates the values for a menu link definition in the menu tree storage.
   *
   * This will update the definition for a discovered menu link without the
   * need for a full rebuild. It is also used for plugins not found through
   * discovery to update definitions.
   *
   * @param string $id
   *   The menu link plugin ID.
   * @param array $new_definition_values
   *   The new values for the link definition. This will usually be just a
   *   subset of the plugin definition.
   * @param bool $persist
   *   TRUE to also have the link instance itself persist the changed values to
   *   any additional storage by invoking MenuLinkInterface::updateDefinition()
   *   on the plugin that is being updated.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   A plugin instance created using the updated definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown if the $id is not a valid, existing, plugin ID.
   */
  public function updateDefinition($id, array $new_definition_values, $persist = TRUE);

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
   *   Thrown if the $id is not a valid, existing, plugin ID or if the link
   *   cannot be reset.
   */
  public function resetLink($id);

  /**
   * Counts the total number of menu links.
   *
   * @param string $menu_name
   *   (optional) The menu name to count by. Defaults to all menus.
   *
   * @return int
   *   The number of menu links in the named menu, or in all menus if the
   *   menu name is NULL.
   */
  public function countMenuLinks($menu_name = NULL);

  /**
   * Loads all parent link IDs of a given menu link.
   *
   * This method is very similar to getActiveTrailIds() but allows the link to
   * be specified rather than being discovered based on the menu name and
   * request. This method is mostly useful for testing.
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
   * Determines if any links use a given menu name.
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
