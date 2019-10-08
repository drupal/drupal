<?php

namespace Drupal\Core\Config;

/**
 * Interface for a storage manager.
 */
interface StorageManagerInterface {

  /**
   * Get the config storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   *   Thrown when the lock could not be acquired.
   */
  public function getStorage();

}
