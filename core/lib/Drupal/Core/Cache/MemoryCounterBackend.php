<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\MemoryCounterBackend.
 */

namespace Drupal\Core\Cache;

/**
 * Defines a memory cache implementation that counts set and get calls.
 *
 * This can be used to mock a cache backend where one needs to know how
 * many times a cache entry was set or requested.
 *
 * @todo On the longrun this backend should be replaced by phpunit mock objects.
 *
 */
class MemoryCounterBackend extends MemoryBackend {

  /**
   * Stores a list of cache cid calls keyed by function name.
   *
   * @var array
   */
  protected $counter = array();

  /**
   * Implements \Drupal\Core\Cache\CacheBackendInterface::get().
   */
  public function get($cid, $allow_invalid = FALSE) {
    $this->increaseCounter(__FUNCTION__, $cid);
    return parent::get($cid, $allow_invalid);
  }

  /**
   * Implements \Drupal\Core\Cache\CacheBackendInterface::set().
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    $this->increaseCounter(__FUNCTION__, $cid);
    parent::set($cid, $data, $expire, $tags);
  }

  /**
   * Implements \Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function delete($cid) {
    $this->increaseCounter(__FUNCTION__, $cid);
    parent::delete($cid);
  }

  /**
   * Increase the counter for a function with a certain cid.
   *
   * @param string $function
   *   The called function.
   *
   * @param string $cid
   *   The cache ID of the cache entry to increase the counter.
   */
  protected function increaseCounter($function, $cid) {
    if (!isset($this->counter[$function][$cid])) {
      $this->counter[$function][$cid] = 1;
    }
    else {
      $this->counter[$function][$cid]++;
    }
  }

  /**
   * Returns the call counter for the get, set and delete methods.
   *
   * @param string $method
   *   (optional) The name of the method to return the call counter for.
   * @param string $cid
   *   (optional) The name of the cache id to return the call counter for.
   *
   * @return int|array
   *   An integer if both method and cid is given, an array otherwise.
   */
  public function getCounter($method = NULL, $cid = NULL) {
    if ($method && $cid) {
      return isset($this->counter[$method][$cid]) ? $this->counter[$method][$cid] : 0;
    }
    elseif ($method) {
      return isset($this->counter[$method]) ? $this->counter[$method] : array();
    }
    else {
      return $this->counter;
    }
  }

  /**
   * Resets the call counter.
   */
  public function resetCounter() {
    $this->counter = array();
  }

}
