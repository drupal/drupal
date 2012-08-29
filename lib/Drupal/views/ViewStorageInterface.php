<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageInterface.
 */

namespace Drupal\views;

use Drupal\config\ConfigurableInterface;

/**
 * Defines an interface for View storage classes.
 */
interface ViewStorageInterface extends ConfigurableInterface {

  /**
   * Sets the configurable entity status to enabled.
   */
  public function enable();

  /**
   * Sets the configurable entity status to disabled.
   */
  public function disable();

  /**
   * Returns whether the configurable entity is enabled.
   *
   * @return bool
   */
  public function isEnabled();

}
