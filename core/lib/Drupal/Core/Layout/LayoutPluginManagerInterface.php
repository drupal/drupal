<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Provides the interface for a plugin manager of layouts.
 */
interface LayoutPluginManagerInterface extends CategorizingPluginManagerInterface {

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
   */
  public function createInstance($plugin_id, array $configuration = []);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition|null
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition[]
   */
  public function getDefinitions();

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition[]
   */
  public function getSortedDefinitions(array $definitions = NULL);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition[][]
   */
  public function getGroupedDefinitions(array $definitions = NULL);

  /**
   * Returns an array of layout labels grouped by category.
   *
   * @return string[][]
   *   A nested array of labels suitable for #options.
   */
  public function getLayoutOptions();

}
