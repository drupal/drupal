<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\Discovery\TestDiscovery.
 */

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Defines test derivative discovery.
 */
class TestDerivativeDiscovery implements DerivativeInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    $definitions = $this->getDerivativeDefinitions($base_plugin_definition);
    return $definitions[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $plugins = array();
    for ($i = 0; $i < 2; $i++) {
      $plugins['test_discovery_' . $i] = $base_plugin_definition;
    }
    return $plugins;
  }

}
