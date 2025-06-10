<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Plugin\FilteredPluginManagerInterface;

/**
 * Provides the interface for a plugin manager of layouts.
 */
interface LayoutPluginManagerInterface extends CategorizingPluginManagerInterface, FilteredPluginManagerInterface {

  /**
   * Gets theme implementations for layouts.
   *
   * @return array
   *   An associative array of the same format as returned by hook_theme().
   *
   * @see hook_theme()
   */
  public function getThemeImplementations();

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   *   The created layout plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = []);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition|null
   *   The plugin definition for the given plugin ID, or NULL if it does not
   *   exist.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   */
  public function getDefinitions();

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition[]
   *   An array of plugin definitions, sorted by category and label.
   */
  public function getSortedDefinitions(?array $definitions = NULL);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition[][]
   *   Keys are category names, and values are arrays of which the keys are
   *   plugin IDs and the values are plugin definitions.
   */
  public function getGroupedDefinitions(?array $definitions = NULL);

  /**
   * Returns an array of layout labels grouped by category.
   *
   * @return string[][]
   *   A nested array of labels suitable for #options.
   */
  public function getLayoutOptions();

}
