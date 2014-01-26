<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\BackendChain.
 */

namespace Drupal\Core\Cache;
/**
 * Defines a chained cache implementation for combining multiple cache backends.
 *
 * Can be used to combine two or more backends together to behave as if they
 * were a single backend.
 *
 * For example a slower, persistent storage engine could be combined with a
 * faster, volatile storage engine. When retrieving items from cache, they will
 * be fetched from the volatile backend first, only falling back to the
 * persistent backend if an item is not available. An item not present in the
 * volatile backend but found in the persistent one will be propagated back up
 * to ensure fast retrieval on the next request. On cache sets and deletes, both
 * backends will be invoked to ensure consistency.
 */

class BackendChain implements CacheBackendInterface {

  /**
   * Ordered list of CacheBackendInterface instances.
   *
   * @var array
   */
  protected $backends = array();

  /**
   * Constructs a DatabaseBackend object.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct($bin) {
  }

  /**
   * Appends a cache backend to the cache chain.
   *
   * @param CacheBackendInterface $backend
   *   The cache backend to be appended to the cache chain.
   *
   * @return \Drupal\Core\Cache\BackendChain
   *   The called object.
   */
  public function appendBackend(CacheBackendInterface $backend) {
    $this->backends[] = $backend;

    return $this;
  }

  /**
   * Prepends a cache backend to the cache chain.
   *
   * @param CacheBackendInterface $backend
   *   The backend to be prepended to the cache chain.
   *
   * @return \Drupal\Core\Cache\BackendChain
   *   The called object.
   */
  public function prependBackend(CacheBackendInterface $backend) {
    array_unshift($this->backends, $backend);

    return $this;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::get().
   */
  public function get($cid, $allow_invalid = FALSE) {
    foreach ($this->backends as $index => $backend) {
      if (($return = $backend->get($cid, $allow_invalid)) !== FALSE) {
        // We found a result, propagate it to all missed backends.
        if ($index > 0) {
          for ($i = ($index - 1); 0 <= $i; --$i) {
            $this->backends[$i]->set($cid, $return->data, $return->expire, $return->tags);
          }
        }

        return $return;
      }
    }

    return FALSE;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $return = array();

    foreach ($this->backends as $index => $backend) {
      $items = $backend->getMultiple($cids, $allow_invalid);

      // Propagate the values that could be retrieved from the current cache
      // backend to all missed backends.
      if ($index > 0 && !empty($items)) {
        for ($i = ($index - 1); 0 <= $i; --$i) {
          foreach ($items as $cached) {
            $this->backends[$i]->set($cached->cid, $cached->data, $cached->expire, $cached->tags);
          }
        }
      }

      // Append the values to the previously retrieved ones.
      $return += $items;

      if (empty($cids)) {
        // No need to go further if we don't have any cid to fetch left.
        break;
      }
    }

    return $return;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::set().
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    foreach ($this->backends as $backend) {
      $backend->set($cid, $data, $expire, $tags);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function delete($cid) {
    foreach ($this->backends as $backend) {
      $backend->delete($cid);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  public function deleteMultiple(array $cids) {
    foreach ($this->backends as $backend) {
      $backend->deleteMultiple($cids);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteTags().
   */
  public function deleteTags(array $tags) {
    foreach ($this->backends as $backend) {
      $backend->deleteTags($tags);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteAll().
   */
  public function deleteAll() {
    foreach ($this->backends as $backend) {
      $backend->deleteAll();
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidate().
   */
  public function invalidate($cid) {
    foreach ($this->backends as $backend) {
      $backend->invalidate($cid);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple().
   */
  public function invalidateMultiple(array $cids) {
    foreach ($this->backends as $backend) {
      $backend->invalidateMultiple($cids);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function invalidateTags(array $tags) {
    foreach ($this->backends as $backend) {
      $backend->invalidateTags($tags);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function invalidateAll() {
    foreach ($this->backends as $backend) {
      $backend->invalidateAll();
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection().
   */
  public function garbageCollection() {
    foreach ($this->backends as $backend) {
      $backend->garbageCollection();
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::isEmpty().
   */
  public function isEmpty() {
    foreach ($this->backends as $backend) {
      if (!$backend->isEmpty()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    foreach ($this->backends as $backend) {
      $backend->removeBin();
    }
  }

}
