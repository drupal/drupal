<?php

/**
 * @file
 * Definition of Drupal\layout\Plugin\LayoutInterface.
 */

namespace Drupal\layout\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the shared interface for all layout plugins.
 */
interface LayoutInterface extends PluginInspectionInterface {

  /**
   * Returns a list of regions.
   *
   * @return array
   *   An array of region machine names.
   */
  public function getRegions();

  /**
   * Renders layout and returns the rendered markup.
   *
   * @return string
   *   Rendered HTML output from the layout.
   */
  public function renderLayout();
}
