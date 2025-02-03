<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Defines a memory cache implementation.
 *
 * Stores cache items in memory using a PHP array.
 *
 * Should be used for unit tests and specialist use-cases only, does not
 * store cached items between requests.
 *
 * The functions ::prepareItem()/::set() use unserialize()/serialize(). It
 * behaves as an external cache backend to avoid changing the cached data by
 * reference. In ::prepareItem(), the object is not modified by the call to
 * unserialize() because we make a clone of it.
 *
 * @ingroup cache
 */
class MemoryBackend implements CacheBackendInterface, CacheTagsInvalidatorInterface {

  /**
   * Array to store cache objects.
   *
   * @var object[]
   */
  protected $cache = [];

  /**
   * Constructs a MemoryBackend object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(protected TimeInterface $time) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    if (isset($this->cache[$cid])) {
      return $this->prepareItem($this->cache[$cid], $allow_invalid);
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $ret = [];

    $items = array_intersect_key($this->cache, array_flip($cids));

    foreach ($items as $item) {
      $item = $this->prepareItem($item, $allow_invalid);
      if ($item) {
        $ret[$item->cid] = $item;
      }
    }

    $cids = array_diff($cids, array_keys($ret));

    return $ret;
  }

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
   *   or been invalidated.
   *
   * @return mixed
   *   The item with data as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cache, $allow_invalid) {
    if (!isset($cache->data)) {
      return FALSE;
    }
    // The object passed into this function is the one stored in $this->cache.
    // We must clone it as part of the preparation step so that the actual
    // cache object is not affected by the unserialize() call or other
    // manipulations of the returned object.

    $prepared = clone $cache;
    $prepared->data = unserialize($prepared->data);

    // Check expire time.
    $prepared->valid = $prepared->expire == Cache::PERMANENT || $prepared->expire >= $this->time->getRequestTime();

    if (!$allow_invalid && !$prepared->valid) {
      return FALSE;
    }

    return $prepared;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    assert(Inspector::assertAllStrings($tags), 'Cache Tags must be strings.');
    $tags = array_unique($tags);
    // Sort the cache tags so that they are stored consistently in the database.
    sort($tags);
    $this->cache[$cid] = (object) [
      'cid' => $cid,
      'data' => serialize($data),
      'created' => $this->time->getRequestTime(),
      'expire' => $expire,
      'tags' => $tags,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = []) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], $item['expire'] ?? CacheBackendInterface::CACHE_PERMANENT, $item['tags'] ?? []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    unset($this->cache[$cid]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $this->cache = array_diff_key($this->cache, array_flip($cids));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->cache = [];
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    if (isset($this->cache[$cid])) {
      $this->cache[$cid]->expire = $this->time->getRequestTime() - 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    $items = array_intersect_key($this->cache, array_flip($cids));
    foreach ($items as $cid => $item) {
      $this->cache[$cid]->expire = $this->time->getRequestTime() - 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($this->cache as $cid => $item) {
      if (array_intersect($tags, $item->tags)) {
        $this->cache[$cid]->expire = $this->time->getRequestTime() - 1;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    @trigger_error("CacheBackendInterface::invalidateAll() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use CacheBackendInterface::deleteAll() or cache tag invalidation instead. See https://www.drupal.org/node/3500622", E_USER_DEPRECATED);
    foreach ($this->cache as $cid => $item) {
      $this->cache[$cid]->expire = $this->time->getRequestTime() - 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->cache = [];
  }

  /**
   * Prevents data stored in memory backends from being serialized.
   */
  public function __sleep(): array {
    return ['time'];
  }

  /**
   * Reset statically cached variables.
   *
   * This is only used by tests.
   */
  public function reset() {
    $this->cache = [];
  }

}
