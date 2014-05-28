<?php

/**
 * @file
 * Contains \Drupal\block\BlockManagerInterface.
 */

namespace Drupal\block;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of block plugins.
 */
interface BlockManagerInterface extends PluginManagerInterface {

  /**
   * Gets the names of all block categories.
   *
   * @return array
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories();

  /**
   * Gets the sorted definitions.
   *
   * @return array
   *   An array of plugin definitions, sorted by category and admin label.
   */
  public function getSortedDefinitions();

}
