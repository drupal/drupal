<?php

namespace Drupal\Component\Plugin;

@trigger_error('The ' . __NAMESPACE__ . '\PluginHelper is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Instead, use instanceof() to check for \Drupal\Component\Plugin\ConfigurableInterface. See https://www.drupal.org/node/3198285', E_USER_DEPRECATED);

/**
 * A helper class to determine if a plugin is configurable.
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Instead, use
 *   instanceof() to check for \Drupal\Component\Plugin\ConfigurableInterface.
 *
 * @see https://www.drupal.org/node/3198285
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
