<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigFactoryOverrideInterface.
 */

namespace Drupal\Core\Config;

/**
 * Defines the interface for a configuration factory override object.
 */
interface ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   *
   * @return array
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   */
  public function loadOverrides($names);

}
