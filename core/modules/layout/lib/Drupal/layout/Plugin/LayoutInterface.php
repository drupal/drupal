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
   *   An associative array of region information keyed by region machine
   *   names. Each region information element is a two item associative array
   *   with a 'label' and a 'type' key designating the human readable label
   *   and the type of the region.
   */
  public function getRegions();

  /**
   * Renders layout and returns the rendered markup.
   *
   * @param bool $admin
   *   (optional) TRUE if the rendered layout is displayed in an administrative
   *   context, FALSE otherwise. Defaults to FALSE.
   * @param array $regions
   *   (optional) An array of region render arrays keyed by region machine
   *   names. Defaults to array.
   *
   * @return string
   *   Rendered HTML output from the layout.
   */
  public function renderLayout($admin = FALSE, $regions = array());
}
