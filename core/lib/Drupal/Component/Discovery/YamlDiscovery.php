<?php

/**
 * @file
 * Contains \Drupal\Component\Discovery\YamlDiscovery.
 */

namespace Drupal\Component\Discovery;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\FileCache\FileCacheFactory;

/**
 * Provides discovery for YAML files within a given set of directories.
 */
class YamlDiscovery implements DiscoverableInterface {

  /**
   * The base filename to look for in each directory.
   *
   * @var string
   */
  protected $name;

  /**
   * An array of directories to scan, keyed by the provider.
   *
   * @var array
   */
  protected $directories = array();

  /**
   * Constructs a YamlDiscovery object.
   *
   * @param string $name
   *   The base filename to look for in each directory. The format will be
   *   $provider.$name.yml.
   * @param array $directories
   *   An array of directories to scan, keyed by the provider.
   */
  public function __construct($name, array $directories) {
    $this->name = $name;
    $this->directories = $directories;
  }

  /**
   * {@inheritdoc}
   */
  public function findAll() {
    $all = array();

    $files = $this->findFiles();
    $provider_by_files = array_flip($files);

    $file_cache = FileCacheFactory::get('yaml_discovery:' . $this->name);

    // Try to load from the file cache first.
    foreach ($file_cache->getMultiple($files) as $file => $data) {
      $all[$provider_by_files[$file]] = $data;
      unset($provider_by_files[$file]);
    }

    // If there are files left that were not returned from the cache, load and
    // parse them now. This list was flipped above and is keyed by filename.
    if ($provider_by_files) {
      foreach ($provider_by_files as $file => $provider) {
        // If a file is empty or its contents are commented out, return an empty
        // array instead of NULL for type consistency.
        $all[$provider] = Yaml::decode(file_get_contents($file)) ?: [];
        $file_cache->set($file, $all[$provider]);
      }
    }

    return $all;
  }

  /**
   * Returns an array of file paths, keyed by provider.
   *
   * @return array
   */
  protected function findFiles() {
    $files = array();
    foreach ($this->directories as $provider => $directory) {
      $file = $directory . '/' . $provider . '.' . $this->name . '.yml';
      if (file_exists($file)) {
        $files[$provider] = $file;
      }
    }
    return $files;
  }

}

