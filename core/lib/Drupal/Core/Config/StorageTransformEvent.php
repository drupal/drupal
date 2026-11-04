<?php

namespace Drupal\Core\Config;

use Drupal\Component\EventDispatcher\Event;

/**
 * Class StorageTransformEvent.
 *
 * This event allows subscribers to alter the configuration of the storage that
 * is being transformed.
 */
class StorageTransformEvent extends Event {

  /**
   * StorageTransformEvent constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage with the configuration to transform.
   */
  public function __construct(
    protected StorageInterface $storage,
  ) {
  }

  /**
   * Returns the mutable storage ready to be read from and written to.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  public function getStorage() {
    return $this->storage;
  }

}
