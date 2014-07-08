<?php

/**
 * @file
 * Contains \Drupal\config_override_test\ConfigOverrider.
 */

namespace Drupal\config_override_test;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;

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
      if (in_array('config_override_test.new', $names)) {
        $overrides = $overrides + array('config_override_test.new' => array('module' => 'override'));
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}

