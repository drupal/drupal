<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigMapperManagerInterface.
 */

namespace Drupal\config_translation;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a common interface for config mapper managers.
 */
interface ConfigMapperManagerInterface extends PluginManagerInterface {

  /**
   * Returns an array of all mappers.
   *
   * @return \Drupal\config_translation\ConfigMapperInterface[]
   *   An array of all mappers.
   */
  public function getMappers();

  /**
   * Returns TRUE if the configuration data has translatable items.
   *
   * @param string $name
   *   Configuration key.
   *
   * @return bool
   *   A boolean indicating if the configuration data has translatable items.
   */
  public function hasTranslatable($name);

}
