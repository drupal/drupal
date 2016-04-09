<?php

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
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {}

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = array()) {}

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {}

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {}

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {}

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {}

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {}

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {}

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {}

  /**
   * {@inheritdoc}
   */
  public function removeBin() {}
}
