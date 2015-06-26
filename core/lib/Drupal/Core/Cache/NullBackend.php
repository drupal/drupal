<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\NullBackend.
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
 *
 * @ingroup cache
 */
class NullBackend implements CacheBackendInterface {

  /**
   * Constructs a NullBackend object.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct($bin) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::get().
   */
  public function get($cid, $allow_invalid = FALSE) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    return array();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::set().
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {}

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = array()) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function delete($cid) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  public function deleteMultiple(array $cids) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteAll().
   */
  public function deleteAll() {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidate().
   */
  public function invalidate($cid) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple().
   */
  public function invalidateMultiple(array $cids) {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function invalidateAll() {}

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection().
   */
  public function garbageCollection() {}

  /**
   * {@inheritdoc}
   */
  public function removeBin() {}
}
