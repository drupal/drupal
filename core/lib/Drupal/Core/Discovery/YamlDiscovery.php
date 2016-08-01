<?php

namespace Drupal\Core\Discovery;

use Drupal\Component\Discovery\YamlDiscovery as ComponentYamlDiscovery;
use Drupal\Core\Serialization\Yaml;

/**
 * Provides discovery for YAML files within a given set of directories.
 *
 * This overrides the Component file decoding with the Core YAML implementation.
 */
class YamlDiscovery extends ComponentYamlDiscovery {

  /**
   * {@inheritdoc}
   */
  protected function decode($file) {
    return Yaml::decode(file_get_contents($file)) ?: [];
  }

}
