<?php

/**
 * @file
 * Definition of Drupal\Component\Plugin\Discovery\DiscoveryInterface.
 */

namespace Drupal\Component\Plugin\Discovery;

/**
 * An interface defining the minimum requirements of building a plugin
 * discovery component.
 */
interface DiscoveryInterface {

  /**
   * Gets a specific plugin definition.
   *
   * @param string $plugin_id
   *   A plugin id.
   *
   * @return array
   *   A plugin definition.
   */
  public function getDefinition($plugin_id);

  /**
   * Gets the definition of all plugins for this type.
   *
   * @return array
   *   An array of plugin definitions.
   */
  public function getDefinitions();

}
