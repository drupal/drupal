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
   * Prefix for keys holding invalidation cache tags.
   *
   * Includes the site-specific prefix in $sitePrefix.
   *
   * @var string
   */
  protected $invalidationsTagsPrefix;

  /**
   * Prefix for keys holding invalidation cache tags.
   *
   * Includes the site-specific prefix in $sitePrefix.
   *
   * @var string
   */
  protected $deletionsTagsPrefix;

  /**
   * A static cache of all tags checked during the request.
   *
   * @var array
   */
  protected static $tagCache = array('deletions' => array(), 'invalidations' => array());

  /**
   * Constructs a new ApcuBackend instance.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param string $site_prefix
   *   The prefix to use for all keys in the storage that belong to this site.
   */
  public function __construct($bin, $site_prefix) {
    $this->bin = $bin;
    $this->sitePrefix = $site_prefix;
    $this->binPrefix = $this->sitePrefix . '::' . $this->bin . '::';
    $this->invalidationsTagsPrefix = $this->sitePrefix . '::itags::';
    $this->deletionsTagsPrefix = $this->sitePrefix . '::dtags::';
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
    foreach ($result as $key => $item) {
      $item = $this->prepareItem($item, $allow_invalid);
      if ($item) {
        $cache[$map[$key]] = $item;
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
    $checksum = $this->checksumTags($cache->tags);

    // Check if deleteTags() has been called with any of the entry's tags.
    if ($cache->checksum_deletions != $checksum['deletions']) {
      return FALSE;
    }

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

    // Check if invalidateTags() has been called with any of the entry's tags.
    if ($cache->checksum_invalidations != $checksum['invalidations']) {
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
    $cache = new \stdClass();
    $cache->cid = $cid;
    $cache->created = round(microtime(TRUE), 3);
    $cache->expire = $expire;
    $cache->tags = implode(' ', $this->flattenTags($tags));
    $checksum = $this->checksumTags($tags);
    $cache->checksum_invalidations = $checksum['invalidations'];
    $cache->checksum_deletions = $checksum['deletions'];
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
  public function isEmpty() {
    return $this->getAll()->getTotalCount() === 0;
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

  /**
   * {@inheritdoc}
   */
  public function deleteTags(array $tags) {
    foreach ($this->flattenTags($tags) as $tag) {
      apc_inc($this->deletionsTagsPrefix . $tag, 1, $success);
      if (!$success) {
        apc_store($this->deletionsTagsPrefix . $tag, 1);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($this->flattenTags($tags) as $tag) {
      apc_inc($this->invalidationsTagsPrefix . $tag, 1, $success);
      if (!$success) {
        apc_store($this->invalidationsTagsPrefix . $tag, 1);
      }
    }
  }

  /**
   * Flattens a tags array into a numeric array suitable for string storage.
   *
   * @param array $tags
   *   Associative array of tags to flatten.
   *
   * @return array
   *   Indexed array of flattened tag identifiers.
   */
  protected function flattenTags(array $tags) {
    if (isset($tags[0])) {
      return $tags;
    }

    $flat_tags = array();
    foreach ($tags as $namespace => $values) {
      if (is_array($values)) {
        foreach ($values as $value) {
          $flat_tags[] = "$namespace:$value";
        }
      }
      else {
        $flat_tags[] = "$namespace:$values";
      }
    }
    return $flat_tags;
  }

  /**
   * Returns the sum total of validations for a given set of tags.
   *
   * @param array $tags
   *   Associative array of tags.
   *
   * @return int
   *   Sum of all invalidations.
   */
  protected function checksumTags(array $tags) {
    $checksum = array('invalidations' => 0, 'deletions' => 0);
    $query_tags = array('invalidations' => array(), 'deletions' => array());

    foreach ($this->flattenTags($tags) as $tag) {
      foreach (array('deletions', 'invalidations') as $type) {
        if (isset(static::$tagCache[$type][$tag])) {
          $checksum[$type] += static::$tagCache[$type][$tag];
        }
        else {
          $query_tags[$type][] = $this->{$type . 'TagsPrefix'} . $tag;
        }
      }
    }

    foreach (array('deletions', 'invalidations') as $type) {
      if ($query_tags[$type]) {
        $result = apc_fetch($query_tags[$type]);
        static::$tagCache[$type] = array_merge(static::$tagCache[$type], $result);
        $checksum[$type] += array_sum($result);
      }
    }

    return $checksum;
  }

}
