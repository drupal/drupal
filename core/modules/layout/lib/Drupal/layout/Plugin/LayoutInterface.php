<?php

/**
 * @file
 * Definition of Drupal\layout\Plugin\LayoutInterface.
 */

namespace Drupal\layout\Plugin;

/**
 * Defines the shared interface for all layout plugins.
 */
interface LayoutInterface {

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
