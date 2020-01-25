<?php

namespace Drupal\Component\Plugin;

/**
 * A helper class to determine if a plugin is configurable.
 *
 * @todo Deprecate this class. https://www.drupal.org/node/3105685
 */
class PluginHelper {

  /**
   * Determines if a plugin is configurable.
   *
   * @param mixed $plugin
   *   The plugin to check.
   *
   * @return bool
   *   A boolean indicating whether the plugin is configurable.
   */
  public static function isConfigurable($plugin) {
    return $plugin instanceof ConfigurableInterface;
  }

}
