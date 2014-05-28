<?php

/**
 * @file
 * Contains \Drupal\system\Entity\MenuInterface.
 */

namespace Drupal\system;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a menu entity.
 */
interface MenuInterface extends ConfigEntityInterface {

  /**
   * Determines if this menu is locked.
   *
   * @return bool
   *   TRUE if the menu is locked, FALSE otherwise.
   */
  public function isLocked();

}
