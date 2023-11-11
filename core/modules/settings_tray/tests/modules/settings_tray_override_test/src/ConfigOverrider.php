<?php

namespace Drupal\settings_tray_override_test;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides an overridden block for Settings Tray testing.
 *
 * @see \Drupal\Tests\settings_tray\FunctionalJavascript\SettingsTrayBlockFormTest::testOverriddenDisabled()
 */
class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('block.block.overridden_block', $names)) {
      if (\Drupal::state()->get('settings_tray_override_test.block')) {
        $overrides = $overrides + ['block.block.overridden_block' => ['settings' => ['label' => 'Now this will be the label.']]];
      }
    }
    if (in_array('system.site', $names)) {
      if (\Drupal::state()->get('settings_tray_override_test.site_name')) {
        $overrides = $overrides + ['system.site' => ['name' => 'Llama Fan Club']];
      }
    }
    if (in_array('system.menu.main', $names)) {
      if (\Drupal::state()->get('settings_tray_override_test.menu')) {
        $overrides = $overrides + ['system.menu.main' => ['label' => 'Foo label']];
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
