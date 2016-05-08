<?php

namespace Drupal\views;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for View storage classes.
 */
interface ViewEntityInterface extends ConfigEntityInterface {

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

  /**
   * Adds a new display handler to the view, automatically creating an ID.
   *
   * @param string $plugin_id
   *   (optional) The plugin type from the Views plugin annotation. Defaults to
   *   'page'.
   * @param string $title
   *   (optional) The title of the display. Defaults to NULL.
   * @param string $id
   *   (optional) The ID to use, e.g., 'default', 'page_1', 'block_2'. Defaults
   *   to NULL.
   *
   * @return string|bool
   *   The key to the display in $view->display, or FALSE if no plugin ID was
   *   provided.
   */
  public function addDisplay($plugin_id = 'page', $title = NULL, $id = NULL);

}
