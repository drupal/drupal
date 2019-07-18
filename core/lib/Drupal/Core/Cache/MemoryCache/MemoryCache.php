<?php

namespace Drupal\Core\Cache\MemoryCache;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Cache\MemoryBackend;

/**
 * Defines a memory cache implementation.
 *
 * Stores cache items in memory using a PHP array. Cache data is not serialized
 * thereby returning the same object as was cached.
 *
 * @ingroup cache
 */
class MemoryCache extends MemoryBackend implements MemoryCacheInterface {

  /**
   * {@inheritdoc}
   */
  protected function prepareItem(array $cache, $allow_invalid = FALSE) {
    if (!isset($cache['data'])) {
      return FALSE;
    }
    $prepared = (object) $cache;
    // Check expire time.
    $prepared->valid = $prepared->expire == static::CACHE_PERMANENT || $prepared->expire >= $this->getRequestTime();

    if (!$allow_invalid && !$prepared->valid) {
      return FALSE;
    }

    return $prepared;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = MemoryCacheInterface::CACHE_PERMANENT, array $tags = []) {
    assert(Inspector::assertAllStrings($tags), 'Cache tags must be strings.');
    $tags = array_unique($tags);

    // Do not create an object at this point to minimize the number of objects
    // garbage collection has to keep a track off.
    $this->cache[$cid] = [
      'cid' => $cid,
      // Note that $data is not serialized.
      'data' => $data,
      'created' => $this->getRequestTime(),
      'expire' => $expire,
      'tags' => $tags,
    ];
  }

}
