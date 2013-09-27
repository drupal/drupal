<?php

/**
 * @file
 * Contains \Drupal\Core\Config\TypedConfigManagerInterface.
 */

namespace Drupal\Core\Config;

/**
 * Defines an interface for typed configuration manager.
 *
 * @package Drupal\Core\Config
 */
Interface TypedConfigManagerInterface {

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
