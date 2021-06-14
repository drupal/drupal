<?php

namespace Drupal\Core\Cache\MemoryCache;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Cache\MemoryBackend;

/**
 * Defines a memory cache implementation.
 *
 * Stores cache items in memory using a PHP array.
 *
 * @ingroup cache
 */
class MemoryCache extends MemoryBackend implements MemoryCacheInterface {

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and returns data
   * as appropriate.
   *
   * @param object $cache
   *   An item loaded from self::get() or self::getMultiple().
   * @param bool $allow_invalid
   *   (optional) If TRUE, cache items may be returned even if they have expired
   *   or been invalidated. Defaults to FALSE.
   *
   * @return mixed
   *   The item with data as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cache, $allow_invalid = FALSE) {
    if (!isset($cache->data)) {
      return FALSE;
    }
    // Check expire time.
    $cache->valid = $cache->expire == static::CACHE_PERMANENT || $cache->expire >= $this->getRequestTime();

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = MemoryCacheInterface::CACHE_PERMANENT, array $tags = []) {
    assert(Inspector::assertAllStrings($tags), 'Cache tags must be strings.');
    $tags = array_unique($tags);

    $this->cache[$cid] = (object) [
      'cid' => $cid,
      'data' => $data,
      'created' => $this->getRequestTime(),
      'expire' => $expire,
      'tags' => $tags,
    ];
  }

}
