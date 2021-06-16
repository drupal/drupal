<?php

namespace Drupal\system;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a menu entity.
 */
interface MenuInterface extends ConfigEntityInterface {

  /**
   * Returns the description of the menu.
   *
   * @return string
   *   Description of the menu.
   */
  public function getDescription();

  /**
   * Determines if this menu is locked.
   *
   * @return bool
   *   TRUE if the menu is locked, FALSE otherwise.
   */
  public function isLocked();

}
