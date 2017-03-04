<?php

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
   * Prepends the APCu user variable prefix for this bin to a cache item ID.
   *
   * @param string $cid
   *   The cache item ID to prefix.
   *
   * @return string
   *   The APCu key for the cache item ID.
   */
  public function getApcuKey($cid) {
    return $this->binPrefix . $cid;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cache = apcu_fetch($this->getApcuKey($cid));
    return $this->prepareItem($cache, $allow_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    // Translate the requested cache item IDs to APCu keys.
    $map = [];
    foreach ($cids as $cid) {
      $map[$this->getApcuKey($cid)] = $cid;
    }

    $result = apcu_fetch(array_keys($map));
    $cache = [];
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
   * apcu_clear_cache() cannot be used, and instead, a list of all cache items
   * belonging to this application need to be retrieved through this method
   * instead.
   *
   * @param string $prefix
   *   (optional) A cache ID prefix to limit the result to.
   *
   * @return \APCUIterator
   *   An APCUIterator containing matched items.
   */
  protected function getAll($prefix = '') {
    return $this->getIterator('/^' . preg_quote($this->getApcuKey($prefix), '/') . '/');
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

    $cache->tags = $cache->tags ? explode(' ', $cache->tags) : [];

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
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = []) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($tags)', 'Cache tags must be strings.');
    $tags = array_unique($tags);
    $cache = new \stdClass();
    $cache->cid = $cid;
    $cache->created = round(microtime(TRUE), 3);
    $cache->expire = $expire;
    $cache->tags = implode(' ', $tags);
    $cache->checksum = $this->checksumProvider->getCurrentChecksum($tags);
    // APCu serializes/unserializes any structure itself.
    $cache->serialized = 0;
    $cache->data = $data;

    // Expiration is handled by our own prepareItem(), not APCu.
    apcu_store($this->getApcuKey($cid), $cache);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = []) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], isset($item['expire']) ? $item['expire'] : CacheBackendInterface::CACHE_PERMANENT, isset($item['tags']) ? $item['tags'] : []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    apcu_delete($this->getApcuKey($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    apcu_delete(array_map([$this, 'getApcuKey'], $cids));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    apcu_delete($this->getIterator('/^' . preg_quote($this->binPrefix, '/') . '/'));
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // APCu performs garbage collection automatically.
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    apcu_delete($this->getIterator('/^' . preg_quote($this->binPrefix, '/') . '/'));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple([$cid]);
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

  /**
   * Instantiates and returns the APCUIterator class.
   *
   * @param mixed $search
   *   A PCRE regular expression that matches against APC key names, either as a
   *   string for a single regular expression, or as an array of regular
   *   expressions. Or, optionally pass in NULL to skip the search.
   * @param int $format
   *   The desired format, as configured with one or more of the APC_ITER_*
   *   constants.
   * @param int $chunk_size
   *   The chunk size. Must be a value greater than 0. The default value is 100.
   * @param int $list
   *   The type to list. Either pass in APC_LIST_ACTIVE or APC_LIST_DELETED.
   *
   * @return \APCUIterator
   */
  protected function getIterator($search = NULL, $format = APC_ITER_ALL, $chunk_size = 100, $list = APC_LIST_ACTIVE) {
    return new \APCUIterator($search, $format, $chunk_size, $list);
  }

}
