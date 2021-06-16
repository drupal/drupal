<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Discovery\YamlDirectoryDiscovery as ComponentYamlDirectoryDiscovery;

/**
 * Allows multiple YAML files per directory to define plugin definitions.
 */
class YamlDirectoryDiscovery extends YamlDiscovery {

  /**
   * Constructs a YamlDirectoryDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider. The value can
   *   either be a string or an array of strings. The string values should be
   *   the path of a directory to scan.
   * @param string $file_cache_key_suffix
   *   The file cache key suffix. This should be unique for each type of
   *   discovery.
   * @param string $key
   *   (optional) The key contained in the discovered data that identifies it.
   *   Defaults to 'id'.
   */
  public function __construct(array $directories, $file_cache_key_suffix, $key = 'id') {
    // Intentionally does not call parent constructor as this class uses a
    // different YAML discovery.
    $this->discovery = new ComponentYamlDirectoryDiscovery($directories, $file_cache_key_suffix, $key);
  }

}
