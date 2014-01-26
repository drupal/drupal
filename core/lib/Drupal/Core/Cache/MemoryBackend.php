<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\ArrayBackend.
 */

namespace Drupal\Core\Cache;

/**
 * Defines a memory cache implementation.
 *
 * Stores cache items in memory using a PHP array.
 *
 * Should be used for unit tests and specialist use-cases only, does not
 * store cached items between requests.
 *
 */
class MemoryBackend implements CacheBackendInterface {

  /**
   * Array to store cache objects.
   */
  protected $cache = array();

  /**
   * Constructs a MemoryBackend object.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct($bin) {
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::get().
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
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $ret = array();

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
   *   An item loaded from cache_get() or cache_get_multiple().
   *
   * @return mixed
   *   The item with data as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cache, $allow_invalid) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    return $cache;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::set().
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    $this->cache[$cid] = (object) array(
      'cid' => $cid,
      'data' => $data,
      'created' => REQUEST_TIME,
      'expire' => $expire,
      'tags' => $this->flattenTags($tags),
    );
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function delete($cid) {
    unset($this->cache[$cid]);
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  public function deleteMultiple(array $cids) {
    $this->cache = array_diff_key($this->cache, array_flip($cids));
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteTags().
   */
  public function deleteTags(array $tags) {
    $flat_tags = $this->flattenTags($tags);
    foreach ($this->cache as $cid => $item) {
      if (array_intersect($flat_tags, $item->tags)) {
        unset($this->cache[$cid]);
      }
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteAll().
   */
  public function deleteAll() {
    $this->cache = array();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidate().
   */
  public function invalidate($cid) {
    if (isset($this->cache[$cid])) {
      $this->cache[$cid]->expire = REQUEST_TIME - 1;
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple().
   */
  public function invalidateMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->cache[$cid]->expire = REQUEST_TIME - 1;
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function invalidateTags(array $tags) {
    $flat_tags = $this->flattenTags($tags);
    foreach ($this->cache as $cid => $item) {
      if (array_intersect($flat_tags, $item->tags)) {
        $this->cache[$cid]->expire = REQUEST_TIME - 1;
      }
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function invalidateAll() {
    foreach ($this->cache as $cid => $item) {
      $this->cache[$cid]->expire = REQUEST_TIME - 1;
    }
  }

  /**
   * 'Flattens' a tags array into an array of strings.
   *
   * @param array $tags
   *   Associative array of tags to flatten.
   *
   * @return array
   *   An indexed array of strings.
   */
  protected function flattenTags(array $tags) {
    if (isset($tags[0])) {
      return $tags;
    }

    $flat_tags = array();
    foreach ($tags as $namespace => $values) {
      if (is_array($values)) {
        foreach ($values as $value) {
          $flat_tags["$namespace:$value"] = "$namespace:$value";
        }
      }
      else {
        $flat_tags["$namespace:$values"] = "$namespace:$values";
      }
    }
    return $flat_tags;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::isEmpty().
   */
  public function isEmpty() {
    return empty($this->cache);
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection()
   */
  public function garbageCollection() {
  }

 /**
  * {@inheritdoc}
  */
  public function removeBin() {}

}
