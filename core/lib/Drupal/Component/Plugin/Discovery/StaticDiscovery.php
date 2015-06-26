<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Discovery\StaticDiscovery.
 */

namespace Drupal\Component\Plugin\Discovery;

/**
 * A discovery mechanism that allows plugin definitions to be manually
 * registered rather than actively discovered.
 */
class StaticDiscovery implements DiscoveryInterface {

  use DiscoveryCachedTrait;

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    if (!$this->definitions) {
      $this->definitions = array();
    }
    return $this->definitions;
  }

  /**
   * Sets a plugin definition.
   */
  public function setDefinition($plugin, $definition) {
    $this->definitions[$plugin] = $definition;
  }

  /**
   * Deletes a plugin definition.
   */
  public function deleteDefinition($plugin) {
    unset($this->definitions[$plugin]);
  }
}
