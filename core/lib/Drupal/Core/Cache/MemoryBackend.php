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
   * All tags invalidated during the request.
   */
  protected $invalidatedTags = array();

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
  public function get($cid) {
    if (isset($this->cache[$cid])) {
      return $this->prepareItem($this->cache[$cid]);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function getMultiple(&$cids) {
    $ret = array();

    $items = array_intersect_key($this->cache, array_flip($cids));

    foreach ($items as $item) {
      $item = $this->prepareItem($item);
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
   * @param stdClass $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   *
   * @return mixed
   *   The item with data as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cache) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    // The cache data is invalid if any of its tags have been cleared since.
    if (count($cache->tags) && $this->hasInvalidatedTags($cache)) {
      return FALSE;
    }

    return $cache;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::set().
   */
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = array()) {
    $this->cache[$cid] = (object) array(
      'cid' => $cid,
      'data' => $data,
      'expire' => $expire,
      'tags' => $tags,
      'checksum' => $this->checksum($this->flattenTags($tags)),
    );
  }

  /**
   * Calculates a checksum so data can be invalidated using tags.
   */
  public function checksum($tags) {
    $checksum = '';

    foreach ($tags as $tag) {
      // Has the tag already been invalidated.
      if (isset($this->invalidatedTags[$tag])) {
        $checksum = $checksum . $tag . ':' . $this->invalidatedTags[$tag];
      }
    }

    return $checksum;
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
   * Implements Drupal\Core\Cache\CacheBackendInterface::flush().
   */
  public function flush() {
    $this->cache = array();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::expire().
   *
   * Cache expiration is not implemented for PHP ArrayBackend as this backend
   * only persists during a single request and expiration are done using
   * REQUEST_TIME.
   */
  public function expire() {
  }

  /**
   * Checks to see if any of the tags associated with a cache object have been
   * invalidated.
   *
   * @param object @cache
   *   An cache object to calculate and compare it's original checksum for.
   *
   * @return boolean
   *   TRUE if the a tag has been invalidated, FALSE otherwise.
   */
  protected function hasInvalidatedTags($cache) {
    if ($cache->checksum != $this->checksum($this->flattenTags($cache->tags))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Flattens a tags array into a numeric array suitable for string storage.
   *
   * @param array $tags
   *   Associative array of tags to flatten.
   *
   * @return
   *   An array of flattened tag identifiers.
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
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function invalidateTags(array $tags) {
    foreach ($this->flattenTags($tags) as $tag) {
      if (isset($this->invalidatedTags[$tag])) {
        $this->invalidatedTags[$tag] = $this->invalidatedTags[$tag] + 1;
      }
      else {
        $this->invalidatedTags[$tag] = 1;
      }
    }
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
}
