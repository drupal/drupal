<?php

namespace Drupal\Core\Update;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\NullBackend;

/**
 * Defines a cache backend for use during Drupal database updates.
 *
 * Passes on deletes to another backend while extending the NullBackend to avoid
 * using anything cached prior to running updates.
 */
class UpdateBackend extends NullBackend {

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
    $this->backend->delete($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $this->backend->deleteMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->backend->deleteAll();
  }

}
