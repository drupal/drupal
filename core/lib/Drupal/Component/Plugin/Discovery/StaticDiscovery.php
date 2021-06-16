<?php

namespace Drupal\Component\Plugin\Discovery;

/**
 * A discovery mechanism that allows plugin definitions to be manually
 * registered rather than actively discovered.
 */
class StaticDiscovery implements DiscoveryInterface {

  use DiscoveryCachedTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    if (!$this->definitions) {
      $this->definitions = [];
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
