<?php

declare(strict_types=1);

namespace Drupal\Core\Utility;

use Drupal\Component\Datetime\Time;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\Lock\NullLockBackend;

/**
 * Caches YAML parsing in a cache collector.
 */
class YamlCacheCollector extends FileParsingCacheCollectorBase {

  /**
   * Creates an instance of the class with a memory cache.
   *
   * This can be used where there is zero expectation of a cache hit rate or in
   * testing.
   */
  public static function createWithMemoryCache(): static {
    return new static('static_cache_key', new MemoryCache(new Time()), new NullLockBackend(), new Time());
  }

  /**
   * {@inheritdoc}
   */
  protected function parseFile($file): array {
    return Yaml::decode($file) ?? [];
  }

}
