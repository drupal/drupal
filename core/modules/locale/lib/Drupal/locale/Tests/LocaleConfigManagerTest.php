<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigManagerTest.
 */

namespace Drupal\locale\Tests;

use Drupal\locale\LocaleConfigManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Config\StorageException;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Provides tests for \Drupal\locale\LocaleConfigManager
 */
class LocaleConfigManagerTest extends DrupalUnitTestBase {

  /**
   * A list of modules to install for this test.
   *
   * @var array
   */
  public static $modules = array('language', 'locale', 'locale_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Locale config manager',
      'description' => 'Tests that the locale config manager operates correctly.',
      'group' => 'Locale',
    );
  }

  /**
   * Tests hasTranslation().
   */
  public function testHasTranslation() {
    $this->installConfig(array('locale_test'));
    $locale_config_manager = new LocaleConfigManager(
      $this->container->get('config.storage'),
      $this->container->get('config.storage.schema'),
      $this->container->get('config.storage.installer'),
      $this->container->get('locale.storage'),
      $this->container->get('cache.config'),
      $this->container->get('config.factory'),
      $this->container->get('language_manager')
    );

    $language = new Language(array('id' => 'de'));
    $this->assertFalse($locale_config_manager->hasTranslation('locale_test.no_translation', $language), 'There is no translation for locale_test.no_translation configuration.');
    $this->assertTrue($locale_config_manager->hasTranslation('locale_test.translation', $language), 'There is a translation for locale_test.translation configuration.');
  }
}
