<?php

/**
 * @file
 * Contains \Drupal\menu_link\Plugin\Core\Entity\MenuLinkInterface.
 */

namespace Drupal\menu_link;

use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\ContentEntityInterface;

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

}
