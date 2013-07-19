<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;

class ContainerDerivativeDiscoveryDecorator extends DerivativeDiscoveryDecorator {

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeFetcher($base_plugin_id, array $base_definition) {
    if (!isset($this->derivativeFetchers[$base_plugin_id])) {
      $this->derivativeFetchers[$base_plugin_id] = FALSE;
      if (isset($base_definition['derivative'])) {
        $class = $base_definition['derivative'];
        // If the derivative class provides a factory method, pass the container
        // to it.
        if (is_subclass_of($class, 'Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface')) {
          $this->derivativeFetchers[$base_plugin_id] = $class::create(\Drupal::getContainer(), $base_plugin_id);
        }
        else {
          $this->derivativeFetchers[$base_plugin_id] = new $class($base_plugin_id);
        }
      }
    }
    return $this->derivativeFetchers[$base_plugin_id] ?: NULL;
  }

}
