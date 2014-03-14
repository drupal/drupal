<?php

/**
 * @file
 * Contains \Drupal\config_override\ConfigOverriderLowPriority.
 */

namespace Drupal\config_override;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Tests module overrides for configuration.
 */
class ConfigOverriderLowPriority implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();
    if (!empty($GLOBALS['config_test_run_module_overrides'])) {
      if (in_array('system.site', $names)) {
        $overrides = array('system.site' =>
          array(
            'name' => 'Should not apply because of higher priority listener',
            // This override should apply because it is not overridden by the
            // higher priority listener.
            'slogan' => 'Yay for overrides!',
          )
        );
      }
    }
    return $overrides;
  }

}

