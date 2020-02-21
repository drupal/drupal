<?php

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
 *
 * @see \Drupal\Core\Cache\ChainedFastBackend
 *
 * @ingroup cache
 */
class BackendChain implements CacheBackendInterface, CacheTagsInvalidatorInterface {

  /**
   * Ordered list of CacheBackendInterface instances.
   *
   * @var array
   */
  protected $backends = [];

  /**
   * Appends a cache backend to the cache chain.
   *
   * @param CacheBackendInterface $backend
   *   The cache backend to be appended to the cache chain.
   *
   * @return $this
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
   * @return $this
   *   The called object.
   */
  public function prependBackend(CacheBackendInterface $backend) {
    array_unshift($this->backends, $backend);

    return $this;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $return = [];

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
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    foreach ($this->backends as $backend) {
      $backend->set($cid, $data, $expire, $tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    foreach ($this->backends as $backend) {
      $backend->setMultiple($items);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    foreach ($this->backends as $backend) {
      $backend->delete($cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    foreach ($this->backends as $backend) {
      $backend->deleteMultiple($cids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    foreach ($this->backends as $backend) {
      $backend->deleteAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    foreach ($this->backends as $backend) {
      $backend->invalidate($cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    foreach ($this->backends as $backend) {
      $backend->invalidateMultiple($cids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($this->backends as $backend) {
      if ($backend instanceof CacheTagsInvalidatorInterface) {
        $backend->invalidateTags($tags);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    foreach ($this->backends as $backend) {
      $backend->invalidateAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    foreach ($this->backends as $backend) {
      $backend->garbageCollection();
    }
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
