<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageInterface.
 */

namespace Drupal\views;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for View storage classes.
 */
interface ViewStorageInterface extends ConfigEntityInterface {

  /**
   * Sets the configuration entity status to enabled.
   */
  public function enable();

  /**
   * Sets the configuration entity status to disabled.
   */
  public function disable();

  /**
   * Returns whether the configuration entity is enabled.
   *
   * @return bool
   */
  public function isEnabled();

}
