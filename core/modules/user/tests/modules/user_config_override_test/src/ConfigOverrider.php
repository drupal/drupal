<?php

declare(strict_types=1);

namespace Drupal\user_config_override_test;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Tests overridden permissions.
 */
class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names): array {
    return [
      'user.role.anonymous' => [
        'permissions' => [9999 => 'access content'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix(): string {
    return 'user_config_override_test';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name): CacheableMetadata {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): StorableConfigBase|null {
    return NULL;
  }

}
