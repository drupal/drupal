<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscoveryWithObject.
 */

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Defines test derivative discovery using an object..
 */
class TestDerivativeDiscoveryWithObject implements DerivativeInterface {

  /**
   * {@inheritdoc}
   * @param string $derivative_id
   * @param array $base_plugin_definition
   * @return array
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $definitions = $this->getDerivativeDefinitions($base_plugin_definition);
    return $definitions[$derivative_id];
  }

  /**
   * {@inheritdoc}
   * @param array $base_plugin_definition
   * @return array
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugins = array();
    for ($i = 0; $i < 2; $i++) {
      $plugins['test_discovery_' . $i] = $base_plugin_definition;
    }
    return $plugins;
  }

}
