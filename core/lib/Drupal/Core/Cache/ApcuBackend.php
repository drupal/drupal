<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\ApcuBackend.
 */

namespace Drupal\Core\Cache;

/**
 * Stores cache items in the Alternative PHP Cache User Cache (APCu).
 */
class ApcuBackend implements CacheBackendInterface {

  /**
   * The name of the cache bin to use.
   *
   * @var string
   */
  protected $bin;

  /**
   * Prefix for all keys in the storage that belong to this site.
   *
   * @var string
   */
  protected $sitePrefix;

  /**
   * Prefix for all keys in this cache bin.
   *
   * Includes the site-specific prefix in $sitePrefix.
   *
   * @var string
   */
  protected $binPrefix;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs a new ApcuBackend instance.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param string $site_prefix
   *   The prefix to use for all keys in the storage that belong to this site.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   */
  public function __construct($bin, $site_prefix, CacheTagsChecksumInterface $checksum_provider) {
    $this->bin = $bin;
    $this->sitePrefix = $site_prefix;
    $this->checksumProvider = $checksum_provider;
    $this->binPrefix = $this->sitePrefix . '::' . $this->bin . '::';
  }

  /**
   * Prepends the APC user variable prefix for this bin to a cache item ID.
   *
   * @param string $cid
   *   The cache item ID to prefix.
   *
   * @return string
   *   The APCu key for the cache item ID.
   */
  protected function getApcuKey($cid) {
    return $this->binPrefix . $cid;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cache = apc_fetch($this->getApcuKey($cid));
    return $this->prepareItem($cache, $allow_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    // Translate the requested cache item IDs to APCu keys.
    $map = array();
    foreach ($cids as $cid) {
      $map[$this->getApcuKey($cid)] = $cid;
    }

    $result = apc_fetch(array_keys($map));
    $cache = array();
    if ($result) {
      foreach ($result as $key => $item) {
        $item = $this->prepareItem($item, $allow_invalid);
        if ($item) {
          $cache[$map[$key]] = $item;
        }
      }
    }
    unset($result);

    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Returns all cached items, optionally limited by a cache ID prefix.
   *
   * APCu is a memory cache, shared across all server processes. To prevent
   * cache item clashes with other applications/installations, every cache item
   * is prefixed with a unique string for this site. Therefore, functions like
   * apc_clear_cache() cannot be used, and instead, a list of all cache items
   * belonging to this application need to be retrieved through this method
   * instead.
   *
   * @param string $prefix
   *   (optional) A cache ID prefix to limit the result to.
   *
   * @return \APCIterator
   *   An APCIterator containing matched items.
   */
  protected function getAll($prefix = '') {
    return new \APCIterator('user', '/^' . preg_quote($this->getApcuKey($prefix), '/') . '/');
  }

  /**
   * Prepares a cached item.
   *
   * Checks that the item is either permanent or did not expire.
   *
   * @param \stdClass $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   * @param bool $allow_invalid
   *   If TRUE, a cache item may be returned even if it is expired or has been
   *   invalidated. See ::get().
   *
   * @return mixed
   *   The cache item or FALSE if the item expired.
   */
  protected function prepareItem($cache, $allow_invalid) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    $cache->tags = $cache->tags ? explode(' ', $cache->tags) : array();

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

    // Check if invalidateTags() has been called with any of the entry's tags.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
      $cache->valid = FALSE;
    }

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = array()) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($tags)', 'Cache tags must be strings.');
    $tags = array_unique($tags);
    $cache = new \stdClass();
    $cache->cid = $cid;
    $cache->created = round(microtime(TRUE), 3);
    $cache->expire = $expire;
    $cache->tags = implode(' ', $tags);
    $cache->checksum = $this->checksumProvider->getCurrentChecksum($tags);
    // APC serializes/unserializes any structure itself.
    $cache->serialized = 0;
    $cache->data = $data;

    // apc_store()'s $ttl argument can be omitted but also set to 0 (zero),
    // in which case the value will persist until it's removed from the cache or
    // until the next cache clear, restart, etc. This is what we want to do
    // when $expire equals CacheBackendInterface::CACHE_PERMANENT.
    if ($expire === CacheBackendInterface::CACHE_PERMANENT) {
      $expire = 0;
    }
    apc_store($this->getApcuKey($cid), $cache, $expire);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = array()) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], isset($item['expire']) ? $item['expire'] : CacheBackendInterface::CACHE_PERMANENT, isset($item['tags']) ? $item['tags'] : array());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    apc_delete($this->getApcuKey($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    apc_delete(array_map(array($this, 'getApcuKey'), $cids));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    apc_delete(new \APCIterator('user', '/^' . preg_quote($this->binPrefix, '/') . '/'));
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // APC performs garbage collection automatically.
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    apc_delete(new \APCIterator('user', '/^' . preg_quote($this->binPrefix, '/') . '/'));
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
    foreach ($this->getMultiple($cids) as $cache) {
      $this->set($cache->cid, $cache, REQUEST_TIME - 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    foreach ($this->getAll() as $data) {
      $cid = str_replace($this->binPrefix, '', $data['key']);
      $this->set($cid, $data['value'], REQUEST_TIME - 1);
    }
  }

}
