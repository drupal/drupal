<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\ChainedFastBackend.
 */

namespace Drupal\Core\Cache;

/**
 * Defines a backend with a fast and a consistent backend chain.
 *
 * In order to mitigate a network roundtrip for each cache get operation, this
 * cache allows a fast backend to be put in front of a slow(er) backend.
 * Typically the fast backend will be something like APCu, and be bound to a
 * single web node, and will not require a network round trip to fetch a cache
 * item. The fast backend will also typically be inconsistent (will only see
 * changes from one web node). The slower backend will be something like Mysql,
 * Mecached or Redis, and will be used by all web nodes, thus making it
 * consistent, but also require a network round trip for each cache get.
 *
 * It is expected this backend will be used primarily on sites running on
 * multiple web nodes, as single-node configurations can just use the fast
 * cache backend directly.
 *
 * We always use the fast backend when reading (get()) entries from cache, but
 * check whether they were created before the last write (set()) to this
 * (chained) cache backend. Those cache entries that were created before the
 * last write are discarded, but we use their cache IDs to then read them from
 * the consistent (slower) cache backend instead; at the same time we update
 * the fast cache backend so that the next read will hit the faster backend
 * again. Hence we can guarantee that the cache entries we return are all
 * up-to-date, and maximally exploit the faster cache backend. This cache
 * backend uses and maintains a "last write timestamp" to determine which cache
 * entries should be discarded.
 *
 * Because this backend will mark all the cache entries in a bin as out-dated
 * for each write to a bin, it is best suited to bins with fewer changes.
 *
 * @ingroup cache
 */
class ChainedFastBackend implements CacheBackendInterface {

  /**
   * Cache key prefix for the bin-specific entry to track the last write.
   */
  const LAST_WRITE_TIMESTAMP_PREFIX = 'last_write_timestamp_';

  /**
   * @var string
   */
  protected $bin;

  /**
   * The consistent cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $consistentBackend;

  /**
   * The fast cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $fastBackend;

  /**
   * The time at which the last write to this cache bin happened.
   *
   * @var int
   */
  protected $lastWriteTimestamp;

  /**
   * Constructs a ChainedFastBackend object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $consistent_backend
   *   The consistent cache backend.
   * @param \Drupal\Core\Cache\CacheBackendInterface $fast_backend
   *   The fast cache backend.
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct(CacheBackendInterface $consistent_backend, CacheBackendInterface $fast_backend, $bin) {
    $this->consistentBackend = $consistent_backend;
    $this->fastBackend = $fast_backend;
    $this->bin = 'cache_' . $bin;
    $this->lastWriteTimestamp = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    // Retrieve as many cache items as possible from the fast backend. (Some
    // cache entries may have been created before the last write to this cache
    // bin and therefore be stale/wrong/inconsistent.)
    $cids_copy = $cids;
    $cache = array();
    $last_write_timestamp = $this->getLastWriteTimestamp();
    if ($last_write_timestamp) {
      foreach ($this->fastBackend->getMultiple($cids, $allow_invalid) as $item) {
        if ($item->created < $last_write_timestamp) {
          $cids[array_search($item->cid, $cids_copy)] = $item->cid;
        }
        else {
          $cache[$item->cid] = $item;
        }
      }
    }

    // If there were any cache entries that were not available in the fast
    // backend, retrieve them from the consistent backend and store them in the
    // fast one.
    if ($cids) {
      foreach ($this->consistentBackend->getMultiple($cids, $allow_invalid) as $item) {
        $cache[$item->cid] = $item;
        $this->fastBackend->set($item->cid, $item->data);
      }
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    $this->markAsOutdated();
    $this->consistentBackend->set($cid, $data, $expire, $tags);
    $this->fastBackend->set($cid, $data, $expire, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    $this->markAsOutdated();
    $this->consistentBackend->setMultiple($items);
    $this->fastBackend->setMultiple($items);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->markAsOutdated();
    $this->consistentBackend->deleteMultiple(array($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $this->markAsOutdated();
    $this->consistentBackend->deleteMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTags(array $tags) {
    $this->markAsOutdated();
    $this->consistentBackend->deleteTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->markAsOutdated();
    $this->consistentBackend->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple(array($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    $this->markAsOutdated();
    $this->consistentBackend->invalidateMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $this->markAsOutdated();
    $this->consistentBackend->invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $this->markAsOutdated();
    $this->consistentBackend->invalidateAll();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $this->consistentBackend->garbageCollection();
    $this->fastBackend->garbageCollection();
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->consistentBackend->removeBin();
    $this->fastBackend->removeBin();
  }

  /**
   * Gets the last write timestamp.
   */
  protected function getLastWriteTimestamp() {
    if ($this->lastWriteTimestamp === NULL) {
      $cache = $this->consistentBackend->get(self::LAST_WRITE_TIMESTAMP_PREFIX . $this->bin);
      $this->lastWriteTimestamp = $cache ? $cache->data : 0;
    }
    return $this->lastWriteTimestamp;
  }

  /**
   * Marks the fast cache bin as outdated because of a write.
   */
  protected function markAsOutdated() {
    // Clocks on a single server can drift. Multiple servers may have slightly
    // differing opinions about the current time. Given that, do not assume
    // 'now' on this server is always later than our stored timestamp.
    $now = microtime(TRUE);
    if ($now > $this->getLastWriteTimestamp()) {
      $this->lastWriteTimestamp = $now;
      $this->consistentBackend->set(self::LAST_WRITE_TIMESTAMP_PREFIX . $this->bin, $this->lastWriteTimestamp);
    }
  }

}
