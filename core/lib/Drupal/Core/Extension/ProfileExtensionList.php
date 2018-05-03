<?php

namespace Drupal\Core\Extension;

/**
 * Provides a list of installation profiles.
 */
class ProfileExtensionList extends ExtensionList {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'dependencies' => [],
    'install' => [],
    'description' => '',
    'package' => 'Other',
    'version' => NULL,
    'php' => DRUPAL_MINIMUM_PHP,
  ];

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    return [$this->installProfile];
  }

}
