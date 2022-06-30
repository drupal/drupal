<?php

namespace Drupal\config_override_test;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Tests module overrides for configuration.
 */
class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (!empty($GLOBALS['config_test_run_module_overrides'])) {
      if (in_array('system.site', $names)) {
        $overrides = $overrides + ['system.site' => ['name' => 'ZOMG overridden site name']];
      }
      if (in_array('config_override_test.new', $names)) {
        $overrides = $overrides + ['config_override_test.new' => ['module' => 'override']];
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

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
