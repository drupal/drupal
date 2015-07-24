<?php

/**
 * @file
 * Contains \Drupal\config_override_integration_test\Cache\ConfigOverrideIntegrationTestCacheContext.
 */

namespace Drupal\config_override_integration_test\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * A cache context service intended for the config override integration test.
 *
 * Cache context ID: 'config_override_integration_test'.
 */
class ConfigOverrideIntegrationTestCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Config override integration test');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Default to the 'disabled' state.
    $state = \Drupal::state()->get('config_override_integration_test.enabled', FALSE) ? 'yes' : 'no';
    return 'config_override_integration_test.' . $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // Since this depends on State this can change at any time and is not
    // cacheable.
    $metadata = new CacheableMetadata();
    $metadata->setCacheMaxAge(0);
    return $metadata;
  }

}
