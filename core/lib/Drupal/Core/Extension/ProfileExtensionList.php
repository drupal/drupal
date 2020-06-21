<?php

namespace Drupal\Core\Extension;

/**
 * Provides a list of installation profiles.
 *
 * @internal
 *   This class is not yet stable and therefore there are no guarantees that the
 *   internal implementations including constructor signature and protected
 *   properties / methods will not change over time. This will be reviewed after
 *   https://www.drupal.org/project/drupal/issues/2940481
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
    'php' => \Drupal::MINIMUM_PHP,
  ];

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    return [$this->installProfile];
  }

}
