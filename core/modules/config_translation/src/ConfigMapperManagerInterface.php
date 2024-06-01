<?php

namespace Drupal\config_translation;

use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a common interface for config mapper managers.
 */
interface ConfigMapperManagerInterface extends PluginManagerInterface {

  /**
   * Returns an array of all mappers.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection used to initialize the mappers.
   *
   * @return \Drupal\config_translation\ConfigMapperInterface[]
   *   An array of all mappers.
   */
  public function getMappers(?RouteCollection $collection = NULL);

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
