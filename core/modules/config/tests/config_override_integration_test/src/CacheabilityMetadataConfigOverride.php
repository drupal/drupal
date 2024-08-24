<?php

declare(strict_types=1);

namespace Drupal\config_override_integration_test;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Test implementation of a config override that provides cacheability metadata.
 */
class CacheabilityMetadataConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Override the test block depending on the state set in the test.
    $state = \Drupal::state()->get('config_override_integration_test.enabled', FALSE);
    if (in_array('block.block.config_override_test', $names) && $state !== FALSE) {
      $overrides = $overrides + [
        'block.block.config_override_test' => [
          'settings' => ['label' => 'Overridden block label'],
        ],
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'config_override_integration_test';
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
    $metadata = new CacheableMetadata();
    if ($name === 'block.block.config_override_test') {
      $metadata
        ->setCacheContexts(['config_override_integration_test'])
        ->setCacheTags(['config_override_integration_test_tag']);
    }
    return $metadata;
  }

}
