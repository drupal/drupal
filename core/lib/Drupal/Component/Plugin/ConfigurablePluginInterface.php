<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\ConfigurablePluginInterface.
 */

namespace Drupal\Component\Plugin;

/**
 * Provides an interface for a configurable plugin.
 */
interface ConfigurablePluginInterface {

  /**
   * Returns this plugin's configuration.
   *
   * @return array
   *   An array of this plugin's configuration.
   */
  public function getConfiguration();

  /**
   * Sets the configuration for this plugin instance.
   *
   * @param array $configuration
   *   An associative array containing the plugin's configuration.
   */
  public function setConfiguration(array $configuration);

  /**
   * Returns default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration();

}
