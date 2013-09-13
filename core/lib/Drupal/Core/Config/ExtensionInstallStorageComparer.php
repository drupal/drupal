<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ExtensionInstallStorageComparer.
 */

namespace Drupal\Core\Config;

/**
 * Defines a config storage comparer.
 */
class ExtensionInstallStorageComparer extends StorageComparer {

  /**
   * Sets the configuration names in the source storage.
   *
   * @param array $source_names
   *   List of all the configuration names in the source storage.
   */
  public function setSourceNames(array $source_names) {
    $this->sourceNames = $source_names;
    return $this;
  }

  /**
   * Gets all the configuration names in the source storage.
   *
   * @return array
   *   List of all the configuration names in the source storage.
   *
   * @see self::setSourceNames()
   */
  protected function getSourceNames() {
    return $this->sourceNames;
  }

}
