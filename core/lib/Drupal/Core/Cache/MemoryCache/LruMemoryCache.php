<?php

namespace Drupal\Core\Cache\MemoryCache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;

/**
 * Defines a least recently used (LRU) static cache implementation.
 *
 * Stores cache items in memory using a PHP array. The number of cache items is
 * limited to a fixed number of slots. When the all slots are full, older items
 * are purged based on least recent usage.
 *
 * @ingroup cache
 */
class LruMemoryCache extends MemoryCache {

  /**
   * Constructs an LruMemoryCache object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param int $allowedSlots
   *   The number of slots to allocate for items in the cache.
   */
  public function __construct(
    TimeInterface $time,
    protected readonly int $allowedSlots,
  ) {
    parent::__construct($time);
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    if ($cached = parent::get($cid, $allow_invalid)) {
      $this->handleCacheHits([$cid => $cached]);
    }
    return $cached;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $ret = parent::getMultiple($cids, $allow_invalid);
    $this->handleCacheHits($ret);
    return $ret;
  }

  /**
   * Moves an array of cache items to the most recently used positions.
   *
   * @param array $items
   *   An array of cache items keyed by cid.
   */
  private function handleCacheHits(array $items): void {
    $last_key = array_key_last($this->cache);
    foreach ($items as $cid => $cached) {
      if ($cached->valid && $cid !== $last_key) {
        // Move valid items to the end of the array, so they will be removed
        // last.
        unset($this->cache[$cid]);
        $this->cache[$cid] = $cached;
        $last_key = $cid;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []): void {
    if (isset($this->cache[$cid])) {
      // If the item is already in the cache, move it to end of the array.
      unset($this->cache[$cid]);
    }
    elseif (count($this->cache) > $this->allowedSlots - 1) {
      // Remove one item from the cache to ensure we remain within the allowed
      // number of slots. Avoid using array_slice() because it makes a copy of
      // the array, and avoid using array_splice() or array_shift() because they
      // re-index numeric keys.
      unset($this->cache[array_key_first($this->cache)]);
    }

    parent::set($cid, $data, $expire, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid): void {
    $this->invalidateMultiple([$cid]);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids): void {
    $items = [];
    foreach ($cids as $cid) {
      if (isset($this->cache[$cid])) {
        $items[$cid] = $this->cache[$cid];
        parent::invalidate($cid);
      }
    }
    $this->moveItemsToLeastRecentlyUsed($items);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags): void {
    $items = [];
    foreach ($this->cache as $cid => $item) {
      if (array_intersect($tags, $item->tags)) {
        parent::invalidate($cid);
        $items[$cid] = $this->cache[$cid];
      }
    }
    $this->moveItemsToLeastRecentlyUsed($items);
  }

  /**
   * Moves items to the least recently used positions.
   *
   * @param array $items
   *   An array of items to move to the least recently used positions.
   */
  private function moveItemsToLeastRecentlyUsed(array $items): void {
    // This cannot use array_unshift() because it would reindex an array with
    // numeric cache IDs.
    if (!empty($items)) {
      $this->cache = $items + $this->cache;
    }
  }

}
