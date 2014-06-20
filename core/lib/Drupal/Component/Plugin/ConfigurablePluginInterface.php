<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\ConfigurablePluginInterface.
 */

namespace Drupal\Component\Plugin;

/**
 * Provides an interface for a configurable plugin.
 *
 * @ingroup plugin_api
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

  /**
   * Calculates dependencies for the configured plugin.
   *
   * Dependencies are saved in the plugin's configuration entity and are used to
   * determine configuration synchronization order. For example, if the plugin
   * integrates with specific user roles, this method should return an array of
   * dependencies listing the specified roles.
   *
   * @return array
   *   An array of dependencies grouped by type (module, theme, entity). For
   *   example:
   *   @code
   *   array(
   *     'entity' => array('user.role.anonymous', 'user.role.authenticated'),
   *     'module' => array('node', 'user'),
   *     'theme' => array('seven'),
   *   );
   *   @endcode
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::getConfigDependencyName()
   */
  public function calculateDependencies();

}
