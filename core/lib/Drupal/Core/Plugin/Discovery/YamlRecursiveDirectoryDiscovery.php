<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Discovery\YamlRecursiveDirectoryDiscovery as ComponentYamlDirectoryDiscovery;

/**
 * Allows multiple YAML files per directory to define plugin definitions.
 */
class YamlRecursiveDirectoryDiscovery extends YamlDirectoryDiscovery {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $directories, $file_cache_key_suffix, $key = 'id', $exclude_pattern = '') {
    // Intentionally does not call parent constructor as this class uses a
    // different YAML discovery.
    $this->discovery = new ComponentYamlDirectoryDiscovery($directories, $file_cache_key_suffix, $key, $exclude_pattern);
  }

}
