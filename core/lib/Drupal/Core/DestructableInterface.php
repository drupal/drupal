<?php

/**
 * @file
 * Contains \Drupal\Core\DestructableInterface.
 */

namespace Drupal\Core;

/**
 * The interface for services that need explicit destruction.
 */
interface DestructableInterface {

  /**
   * Performs destruct operations.
   */
  public function destruct();
}
