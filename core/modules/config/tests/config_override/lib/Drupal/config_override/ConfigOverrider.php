<?php

/**
 * @file
 * Contains \Drupal\config_override\ConfigOverrider.
 */

namespace Drupal\config_override;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\ConfigModuleOverridesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests module overrides for configuration.
 */
class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();
    if (!empty($GLOBALS['config_test_run_module_overrides'])) {
      if (in_array('system.site', $names)) {
        $overrides = $overrides + array('system.site' => array('name' => 'ZOMG overridden site name'));
      }
      if (in_array('config_override.new', $names)) {
        $overrides = $overrides + array('config_override.new' => array('module' => 'override'));
      }
    }
    return $overrides;
  }

}

