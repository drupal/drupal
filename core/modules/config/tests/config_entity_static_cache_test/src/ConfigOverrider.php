<?php

namespace Drupal\config_entity_static_cache_test;

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
    return array(
      'config_test.dynamic.test_1' => array(
        'label' => 'Overridden label',
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'config_entity_static_cache_test';
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
