<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuLinkStorageControllerInterface.
*/

namespace Drupal\menu_link;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for menu link entity controller classes.
 */
interface MenuLinkStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Sets an internal flag that allows us to prevent the reparenting operations
   * executed during deletion.
   *
   * @param bool $value
   *   TRUE if reparenting should be allowed, FALSE if it should be prevented.
   */
  public function setPreventReparenting($value = FALSE);

  /**
   * Gets value of internal flag that allows/prevents reparenting operations
   * executed during deletion.
   *
   * @return bool
   *   TRUE if reparenting is allowed, FALSE if it is prevented.
   */
  public function getPreventReparenting();

  /**
   * Loads system menu link as needed by system_get_module_admin_tasks().
   *
   * @return array
   *   An array of menu link entities indexed by their IDs.
   */
  public function loadModuleAdminTasks();

  /**
   * Checks and updates the 'has_children' property for the parent of a link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   */
  public function updateParentalStatus(EntityInterface $entity, $exclude = FALSE);

  /**
   * Finds the depth of an item's children relative to its depth.
   *
   * For example, if the item has a depth of 2 and the maximum of any child in
   * the menu link tree is 5, the relative depth is 3.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   *
   * @return int
   *   The relative depth, or zero.
   */
  public function findChildrenRelativeDepth(EntityInterface $entity);

  /**
   * Updates the children of a menu link that is being moved.
   *
   * The menu name, parents (p1 - p6), and depth are updated for all children of
   * the link, and the has_children status of the previous parent is updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   */
  public function moveChildren(EntityInterface $entity);

  /**
   * Returns the number of menu links from a menu.
   *
   * @param string $menu_name
   *   The unique name of a menu.
   */
  public function countMenuLinks($menu_name);

  /**
   * Tries to derive menu link's parent from the path hierarchy.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   A menu link entity or FALSE if not valid parent was found.
   */
  public function getParentFromHierarchy(EntityInterface $entity);

  /**
   * Builds a menu link entity from a default item.
   *
   * This function should only be called for link data from
   * hook_menu_link_defaults().
   *
   * @param array $item
   *   An item returned from menu_links_get_defaults().
   *
   * @return \Drupal\menu_link\MenuLinkInterface
   *   A menu link entity.
   */
  public function createFromDefaultLink(array $item);

}
