<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\CategorizingPluginManagerInterface.
 */

namespace Drupal\Component\Plugin;

/**
 * Defines an interface for plugin managers that categorize plugin definitions.
 */
interface CategorizingPluginManagerInterface extends PluginManagerInterface {

  /**
   * Gets the names of all categories.
   *
   * @return string[]
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories();

  /**
   * Gets sorted plugin definitions.
   *
   * @param array[]|null $definitions
   *   (optional) The plugin definitions to sort. If omitted, all plugin
   *   definitions are used.
   *
   * @return array[]
   *   An array of plugin definitions, sorted by category and label.
   */
  public function getSortedDefinitions(array $definitions = NULL);

  /**
   * Gets sorted plugin definitions grouped by category.
   *
   * In addition to grouping, both categories and its entries are sorted,
   * whereas plugin definitions are sorted by label.
   *
   * @param array[]|null $definitions
   *   (optional) The plugin definitions to group. If omitted, all plugin
   *   definitions are used.
   *
   * @return array[]
   *   Keys are category names, and values are arrays of which the keys are
   *   plugin IDs and the values are plugin definitions.
   */
  public function getGroupedDefinitions(array $definitions = NULL);

}
