<?php

namespace Drupal\Component\Plugin;

/**
 * Plugin interface for providing some metadata inspection.
 *
 * This interface provides some simple tools for code receiving a plugin to
 * interact with the plugin system.
 *
 * @ingroup plugin_api
 */
interface PluginInspectionInterface {

  /**
   * Gets the plugin_id of the plugin instance.
   *
   * @return string
   *   The plugin_id of the plugin instance.
   */
  public function getPluginId();

  /**
   * Gets the definition of the plugin implementation.
   *
   * @return array
   *   The plugin definition, as returned by the discovery object used by the
   *   plugin manager.
   */
  public function getPluginDefinition();

  /**
   * Checks if the plugin is deprecated.
   *
   * @return bool
   *   Returns TRUE if the plugin is deprecated, FALSE otherwise.
   */
  public function isDeprecated(): bool;

  /**
   * Gets the deprecation message.
   *
   * @return string|null
   *   Message detailing what is deprecated and the alternative to use.
   */
  public function getDeprecationMessage(): ?string;

}
