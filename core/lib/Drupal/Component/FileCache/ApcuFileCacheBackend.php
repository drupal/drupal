<?php

namespace Drupal\Component\FileCache;

/**
 * APCu backend for the file cache.
 */
class ApcuFileCacheBackend implements FileCacheBackendInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    return apcu_fetch($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data) {
    apcu_store($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    apcu_delete($cid);
  }

}
