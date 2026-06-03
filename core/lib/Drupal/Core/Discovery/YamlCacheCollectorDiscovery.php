<?php

declare(strict_types=1);

namespace Drupal\Core\Discovery;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * Provides discovery for YAML files within a given set of directories.
 *
 * This overrides the Component file decoding with the Core YAML implementation.
 */
class YamlCacheCollectorDiscovery extends YamlDiscovery {

  /**
   * Constructs a YamlDiscovery object.
   *
   * @param string $name
   *   The base filename to look for in each directory. The format will be
   *   $provider.$name.yml.
   * @param array $directories
   *   An array of directories to scan, keyed by the provider.
   * @param Drupal\Core\Cache\CacheCollectorInterface $yamlCacheCollector
   *   An instance of YamlCacheCollector.
   */
  public function __construct(string $name, array $directories, protected CacheCollectorInterface $yamlCacheCollector) {
    parent::__construct($name, $directories);
  }

  /**
   * {@inheritdoc}
   */
  public function findAll(): array {
    $all = [];

    $files = $this->findFiles();
    foreach ($files as $provider => $file) {
      $all[$provider] = $this->yamlCacheCollector->get($file);
    }
    // Once discovery is complete, call ::destruct() on the cache collector to
    // free up memory.
    $this->yamlCacheCollector->destruct();
    return $all;
  }

  /**
   * {@inheritdoc}
   */
  protected function decode($file): array {

    try {
      return $this->yamlCacheCollector->get($file);
    }
    catch (InvalidDataTypeException $e) {
      throw new InvalidDataTypeException($file . ': ' . $e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Calls cache collector ::destruct() method when this goes out of scope.
   */
  public function __destruct() {
    $this->yamlCacheCollector->destruct();
  }

}
