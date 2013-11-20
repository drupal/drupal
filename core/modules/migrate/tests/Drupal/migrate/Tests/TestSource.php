<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\TestSource.
 */


namespace Drupal\migrate\Tests;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\migrate\Source;

class TestSource extends Source {
  function setCache(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }
}
