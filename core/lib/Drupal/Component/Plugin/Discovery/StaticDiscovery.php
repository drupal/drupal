<?php

namespace Drupal\Component\Plugin\Discovery;

/**
 * Allows plugin definitions to be manually registered.
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
