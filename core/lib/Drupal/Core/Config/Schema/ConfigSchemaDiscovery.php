<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\ConfigSchemaDiscovery.
 */

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
   * A storage instance for reading configuration schema data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $schemaStorage;

  /**
   * Constructs a ConfigSchemaDiscovery object.
   *
   * @param $schema_storage
   *   The storage object to use for reading schema data.
   */
  function __construct(StorageInterface $schema_storage) {
    $this->schemaStorage = $schema_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = array();
    foreach ($this->schemaStorage->readMultiple($this->schemaStorage->listAll()) as $schema) {
      foreach ($schema as $type => $definition) {
        $definitions[$type] = $definition;
      }
    }
    return $definitions;
  }
}
