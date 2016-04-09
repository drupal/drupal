<?php

namespace Drupal\config_override_test;

use Drupal\config_override_test\Cache\PirateDayCacheContext;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Test implementation of a config override that provides cacheability metadata.
 */
class PirateDayCacheabilityMetadataConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Override the theme and the 'call_to_action' block on Pirate Day.
    if (PirateDayCacheContext::isPirateDay()) {
      if (in_array('system.theme', $names)) {
        $overrides = $overrides + ['system.theme' => ['default' => 'pirate']];
      }
      if (in_array('block.block.call_to_action', $names)) {
        $overrides = $overrides + [
          'block.block.call_to_action' => [
            'settings' => ['label' => 'Draw yer cutlasses!'],
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
    return 'PirateDayConfigOverrider';
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
    $metadata
      ->setCacheContexts(['pirate_day'])
      ->setCacheTags(['pirate-day-tag'])
      ->setCacheMaxAge(PirateDayCacheContext::PIRATE_DAY_MAX_AGE);
    return $metadata;
  }

  /**
   * Returns whether or not our overrides are potentially applicable.
   *
   * @param string $name
   *   The name of the config object that is being constructed.
   *
   * @return bool
   *   TRUE if the merchant ship will be boarded. FALSE if we drink rum instead.
   */
  protected function isCacheabilityMetadataApplicable($name) {
    return in_array($name, ['system.theme', 'block.block.call_to_action']);
  }

}
