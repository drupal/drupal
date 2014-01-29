<?php

/**
 * @file
 * Contains \Drupal\Core\Config\TypedConfigManagerInterface.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for typed configuration manager.
 *
 * @package Drupal\Core\Config
 */
Interface TypedConfigManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Checks if the configuration schema with the given config name exists.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return bool
   *   TRUE if configuration schema exists, FALSE otherwise.
   */
  public function hasConfigSchema($name);

}
