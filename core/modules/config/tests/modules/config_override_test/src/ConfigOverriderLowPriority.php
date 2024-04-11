<?php

namespace Drupal\config_override_test;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Tests module overrides for configuration.
 */
class ConfigOverriderLowPriority implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (!empty($GLOBALS['config_test_run_module_overrides'])) {
      if (in_array('system.site', $names)) {
        $overrides = [
          'system.site' => [
            'name' => 'Should not apply because of higher priority listener',
            // This override should apply because it is not overridden by the
            // higher priority listener.
            'slogan' => 'Yay for overrides!',
          ],
        ];
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigOverriderLowPriority';
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
