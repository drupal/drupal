<?php

namespace Drupal\Component\Plugin\Factory;

/**
 * Factory interface implemented by all plugin factories.
 */
interface FactoryInterface {

  /**
   * Creates a plugin instance based on the provided ID and configuration.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return object
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = []);

}
