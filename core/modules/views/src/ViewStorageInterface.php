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
   * Retrieves a specific display's configuration by reference.
   *
   * @param string $display_id
   *   The display ID to retrieve, e.g., 'default', 'page_1', 'block_2'.
   *
   * @return array
   *   A reference to the specified display configuration.
   */
  public function &getDisplay($display_id);

  /**
   * Add defaults to the display options.
   */
  public function mergeDefaultDisplaysOptions();

}
