<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Core\File\FileSystemInterface;

/**
 * Discover directories that contain a specific metadata file.
 */
class DirectoryWithMetadataPluginDiscovery extends YamlDiscovery {

  /**
   * Constructs a DirectoryWithMetadataPluginDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider. The value can
   *   either be a string or an array of strings. The string values should be
   *   the path of a directory to scan.
   * @param string $file_cache_key_suffix
   *   The file cache key suffix. This should be unique for each type of
   *   discovery.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $directories, $file_cache_key_suffix, FileSystemInterface $file_system) {
    // Intentionally does not call parent constructor as this class uses a
    // different YAML discovery.
    $this->discovery = new DirectoryWithMetadataDiscovery($directories, $file_cache_key_suffix, $file_system);
  }

}
