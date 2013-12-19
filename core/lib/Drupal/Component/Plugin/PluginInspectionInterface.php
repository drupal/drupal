<?php
/**
 * @file
 * Contains \Drupal\Component\Plugin\PluginInspectionInterface.
 */

namespace Drupal\Component\Plugin;

/**
 * Plugin interface for providing some metadata inspection.
 *
 * This interface provides some simple tools for code receiving a plugin to
 * interact with the plugin system.
 */
interface PluginInspectionInterface {

  /**
   * Returns the plugin_id of the plugin instance.
   *
   * @return string
   *   The plugin_id of the plugin instance.
   */
  public function getPluginId();

  /**
   * Returns the definition of the plugin implementation.
   *
   * @return array
   *   The plugin definition, as returned by the discovery object used by the
   *   plugin manager.
   */
  public function getPluginDefinition();

}
