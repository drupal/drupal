<?php

/**
 * @file
 * Contains \Drupal\Component\Discovery\YamlDiscovery.
 */

namespace Drupal\Component\Discovery;

use Drupal\Component\Serialization\Yaml;

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
    foreach ($this->findFiles() as $provider => $file) {
      $all[$provider] = Yaml::decode(file_get_contents($file));
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

