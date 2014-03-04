<?php
/**
 * @file
 * Definition of Drupal\Component\Plugin\Factory\FactoryInterface.
 */

namespace Drupal\Component\Plugin\Factory;

/**
 * Factory interface implemented by all plugin factories.
 */
interface FactoryInterface {

  /**
   * Returns a preconfigured instance of a plugin.
   *
   * @param string $plugin_id
   *   The id of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return object
   *   A fully configured plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = array());

}
