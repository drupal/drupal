<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuLinkInterface.
 */

namespace Drupal\menu_link;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an interface defining a menu link entity.
 */
interface MenuLinkInterface extends EntityInterface {

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
   * This should only be called by MenuLinkStorage when loading
   * the link object. Calling it at other times could result in unpredictable
   * behavior.
   *
   * @param \Symfony\Component\Routing\Route $route
   */
  public function setRouteObject(Route $route);

  /**
   * Resets a system-defined menu link.
   *
   * @return \Drupal\menu_link\MenuLinkInterface
   *   A menu link entity.
   */
  public function reset();

}
