<?php

/**
 * @file
 * Contains \Drupal\Core\Block\BlockManagerInterface.
 */

namespace Drupal\Core\Block;

use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of block plugins.
 */
interface BlockManagerInterface extends ContextAwarePluginManagerInterface {

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
