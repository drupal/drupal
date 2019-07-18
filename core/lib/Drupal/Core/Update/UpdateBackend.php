<?php

namespace Drupal\Core\Update;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryBackend;

/**
 * Defines a cache backend for use during updating Drupal.
 *
 * Passes on deletes to another backend while using a memory backend to avoid
 * using anything cached prior to running updates.
 */
class UpdateBackend extends MemoryBackend {

  /**
   * The regular runtime cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $backend;

  /**
   * UpdateBackend constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The regular runtime cache backend.
   */
  public function __construct(CacheBackendInterface $backend) {
    $this->backend = $backend;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    parent::delete($cid);
    $this->backend->delete($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    parent::deleteMultiple($cids);
    $this->backend->deleteMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    parent::deleteAll();
    $this->backend->deleteAll();
  }

}
