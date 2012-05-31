<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\NullBackend.
 */

namespace Drupal\Core\Cache;

/**
 * Defines a stub cache implementation.
 *
 * The stub implementation is needed when database access is not yet available.
 * Because Drupal's caching system never requires that cached data be present,
 * these stub functions can short-circuit the process and sidestep the need for
 * any persistent storage. Using this cache implementation during normal
 * operations would have a negative impact on performance.
 *
 * This also can be used for testing purposes.
 */
class NullBackend implements CacheBackendInterface {

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::__construct().
   */
  function __construct($bin) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::get().
   */
  function get($cid) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  function getMultiple(&$cids) {
    return array();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::set().
   */
  function set($cid, $data, $expire = CACHE_PERMANENT, array $tags = array()) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  function delete($cid) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  function deleteMultiple(array $cids) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deletePrefix().
   */
  function deletePrefix($prefix) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::flush().
   */
  function flush() {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::expire().
   */
  function expire() {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection().
   */
  function garbageCollection() {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function invalidateTags(array $tags) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::isEmpty().
   */
  function isEmpty() {
    return TRUE;
  }
}
