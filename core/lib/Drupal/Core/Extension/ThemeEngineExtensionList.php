<?php

namespace Drupal\Core\Extension;

/**
 * Provides a list of available theme engines.
 *
 * @internal
 *   This class is not yet stable and therefore there are no guarantees that the
 *   internal implementations including constructor signature and protected
 *   properties / methods will not change over time. This will be reviewed after
 *   https://www.drupal.org/project/drupal/issues/2940481
 */
class ThemeEngineExtensionList extends ExtensionList {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'dependencies' => [],
    'description' => '',
    'package' => 'Other',
    'version' => NULL,
    'php' => DRUPAL_MINIMUM_PHP,
  ];

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    // Theme engines do not have an 'install' state, so return names of all
    // discovered theme engines.
    return array_keys($this->extensions);
  }

}
