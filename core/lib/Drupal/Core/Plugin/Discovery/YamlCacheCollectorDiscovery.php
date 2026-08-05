<?php

declare(strict_types=1);

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Core\Cache\CacheCollectorInterface;
use Drupal\Core\Discovery\YamlCacheCollectorDiscovery as YamlCollector;

/**
 * Extends YamlDiscovery to add a file parsing cache collector.
 */
class YamlCacheCollectorDiscovery extends YamlDiscovery {

  /**
   * Construct a YamlDiscovery object.
   *
   * @param string $name
   *   The file name suffix to use for discovery; for example, 'test' will
   *   become 'MODULE.test.yml'.
   * @param array $directories
   *   An array of directories to scan.
   * @param Drupal\Core\Cache\CacheCollectorInterface $yamlCacheCollector
   *   A YamlCacheCollector instance.
   */
  public function __construct(string $name, array $directories, CacheCollectorInterface $yamlCacheCollector) {
    $this->discovery = new YamlCollector($name, $directories, $yamlCacheCollector);
  }

}
