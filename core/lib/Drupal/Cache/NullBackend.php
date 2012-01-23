<?php

/**
 * @file
 * Definition of NullBackend.
 */

namespace Drupal\Cache;

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
   * Implements Drupal\Cache\CacheBackendInterface::__construct().
   */
  function __construct($bin) {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::get().
   */
  function get($cid) {
    return FALSE;
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::getMultiple().
   */
  function getMultiple(&$cids) {
    return array();
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::set().
   */
  function set($cid, $data, $expire = CACHE_PERMANENT) {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::delete().
   */
  function delete($cid) {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::deleteMultiple().
   */
  function deleteMultiple(array $cids) {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::deletePrefix().
   */
  function deletePrefix($prefix) {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::flush().
   */
  function flush() {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::expire().
   */
  function expire() {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::garbageCollection().
   */
  function garbageCollection() {}

  /**
   * Implements Drupal\Cache\CacheBackendInterface::isEmpty().
   */
  function isEmpty() {
    return TRUE;
  }
}
