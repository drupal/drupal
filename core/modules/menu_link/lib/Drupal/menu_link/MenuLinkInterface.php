<?php

/**
 * @file
 * Contains \Drupal\menu_link\Entity\MenuLinkInterface.
 */

namespace Drupal\menu_link;

use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Provides an interface defining a menu link entity.
 */
interface MenuLinkInterface extends ContentEntityInterface {

  /**
   * Returns the Route object associated with this link, if any.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The route object for this menu link, or NULL if there isn't one.
   */
  public function getRoute();

  /**
   * Sets the route object for this link.
   *
   * This should only be called by MenuLinkStorageController when loading
   * the link object. Calling it at other times could result in unpredictable
   * behavior.
   *
   * @param \Symfony\Component\Routing\Route $route
   */
  public function setRouteObject(Route $route);

  /**
   * Resets a system-defined menu link.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A menu link entity.
   */
  public function reset();

  /**
   * Builds a menu link entity from a router item.
   *
   * @param array $item
   *   A menu router item.
   *
   * @return \Drupal\menu_link\MenuLinkInterface
   *   A menu link entity.
   */
  public static function buildFromRouterItem(array $item);

  /**
   * Returns the route_name and route parameters matching a system path.
   *
   * @param string $link_path
   *   The link path to find a route name for.
   *
   * @return array
   *   Returns an array with both the route name and parameters, or an empty
   *   array if no route was matched.
   */
  public static function findRouteNameParameters($link_path);

  /**
   * Sets the p1 through p9 properties for a menu link entity being saved.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   A menu link entity.
   */
  public function setParents(EntityInterface $parent);

  /**
   * Finds a possible parent for a given menu link entity.
   *
   * Because the parent of a given link might not exist anymore in the database,
   * we apply a set of heuristics to determine a proper parent:
   *
   *  - use the passed parent link if specified and existing.
   *  - else, use the first existing link down the previous link hierarchy
   *  - else, for system menu links (derived from hook_menu()), reparent
   *    based on the path hierarchy.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   Storage controller object.
   * @param array $parent_candidates
   *   An array of menu link entities keyed by mlid.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   A menu link entity structure of the possible parent or FALSE if no valid
   *   parent has been found.
   */
  public function findParent(EntityStorageControllerInterface $storage_controller, array $parent_candidates = array());
}
