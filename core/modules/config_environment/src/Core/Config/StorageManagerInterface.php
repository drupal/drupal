<?php

// @codingStandardsIgnoreStart
// @todo: Move this back to \Drupal\Core\Config in #2991683.
// Use this class with its class alias Drupal\Core\Config\StorageManagerInterface
// @codingStandardsIgnoreEnd
namespace Drupal\config_environment\Core\Config;

/**
 * Interface for a storage manager.
 *
 * @internal
 */
interface StorageManagerInterface {

  /**
   * Get the config storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  public function getStorage();

}
