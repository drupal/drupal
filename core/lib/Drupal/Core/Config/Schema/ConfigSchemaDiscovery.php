<?php

namespace Drupal\Core\Config\Schema;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Core\Config\StorageInterface;

/**
 * Allows YAML files to define config schema types.
 */
class ConfigSchemaDiscovery implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * Constructs a ConfigSchemaDiscovery object.
   *
   * @param \Drupal\Core\Config\StorageInterface $schemaStorage
   *   The storage object to use for reading schema data.
   */
  public function __construct(
    protected StorageInterface $schemaStorage,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = [];
    foreach ($this->schemaStorage->readMultiple($this->schemaStorage->listAll()) as $schema) {
      foreach ($schema as $type => $definition) {
        $definitions[$type] = $definition;
      }
    }
    return $definitions;
  }

}
