<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Cache\CacheCollectorHelper.
 */

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\CacheCollector;

/**
 * Helper class to test the cache collector.
 */
class CacheCollectorHelper extends CacheCollector {

  /**
   * Contains data to return on a cache miss.
   * @var array
   */
  protected $cacheMissData = array();

  /**
   * Number of calls to \Drupal\Core\Cache\CacheCollector::resolveCacheMiss().
   *
   * @var int
   */
  protected $cacheMisses = 0;

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    parent::set($key, $value);
    $this->persist($key);
  }

  /**
   * {@inheritdoc}
   */
  public function resolveCacheMiss($key) {
    $this->cacheMisses++;
    if (isset($this->cacheMissData[$key])) {
      $this->storage[$key] = $this->cacheMissData[$key];
      $this->persist($key);
      return $this->cacheMissData[$key];
    }
  }

  /**
   * Sets data to return from a cache miss resolve.
   *
   * @param string $key
   *   The key being looked for.
   * @param mixed $value
   *   The value to return.
   */
  public function setCacheMissData($key, $value) {
    $this->cacheMissData[$key] = $value;
  }

  /**
   * Returns the number of cache misses.
   *
   * @return int
   *   Number of calls to the resolve cache miss method.
   */
  public function getCacheMisses() {
    return $this->cacheMisses;
  }

}
