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
   * Gets an executable instance for this view.
   *
   * @return \Drupal\views\ViewExecutable
   *   A view executable instance.
   */
  public function getExecutable();

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

  /**
   * Duplicates an existing display into a new display type.
   *
   * For example clone to display a page display as a block display.
   *
   * @param string $old_display_id
   *   The origin of the duplicated display.
   * @param string $new_display_type
   *   The display type of the new display.
   *
   * @return string
   *   The display ID of the new display.
   */
  public function duplicateDisplayAsType($old_display_id, $new_display_type);

}
