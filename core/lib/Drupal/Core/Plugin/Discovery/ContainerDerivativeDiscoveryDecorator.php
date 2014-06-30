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
  protected function getDeriver($base_plugin_id, $base_definition) {
    if (!isset($this->derivers[$base_plugin_id])) {
      $this->derivers[$base_plugin_id] = FALSE;
      $class = $this->getDeriverClass($base_definition);
      if ($class) {
        // If the deriver provides a factory method, pass the container to it.
        if (is_subclass_of($class, '\Drupal\Core\Plugin\Discovery\ContainerDeriverInterface')) {
          /** @var \Drupal\Core\Plugin\Discovery\ContainerDeriverInterface $class */
          $this->derivers[$base_plugin_id] = $class::create(\Drupal::getContainer(), $base_plugin_id);
        }
        else {
          $this->derivers[$base_plugin_id] = new $class($base_plugin_id);
        }
      }
    }
    return $this->derivers[$base_plugin_id] ?: NULL;
  }

}
