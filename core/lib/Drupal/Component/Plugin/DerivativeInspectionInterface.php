<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\DerivativeInspectionInterface.
 */

namespace Drupal\Component\Plugin;

/**
 * Provides a plugin interface for providing derivative metadata inspection.
 */
interface DerivativeInspectionInterface {

  /**
   * Gets the base_plugin_id of the plugin instance.
   *
   * @return string
   *   The base_plugin_id of the plugin instance.
   */
  public function getBaseId();

  /**
   * Gets the derivative_id of the plugin instance.
   *
   * @return string|null
   *   The derivative_id of the plugin instance NULL otherwise.
   */
  public function getDerivativeId();

}
