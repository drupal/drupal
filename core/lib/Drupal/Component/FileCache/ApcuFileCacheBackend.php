<?php

/**
 * @file
 * Contains \Drupal\Component\FileCache\ApcuFileCacheBackend.
 */

namespace Drupal\Component\FileCache;

/**
 * APCu backend for the file cache.
 */
class ApcuFileCacheBackend implements FileCacheBackendInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    return apc_fetch($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data) {
    apc_store($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    apc_delete($cid);
  }

}
