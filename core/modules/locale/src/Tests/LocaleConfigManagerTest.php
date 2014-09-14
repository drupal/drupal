<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigManagerTest.
 */

namespace Drupal\locale\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\LocaleConfigManager;
use Drupal\Core\Config\StorageException;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests that the locale config manager operates correctly.
 *
 * @group locale
 */
class LocaleConfigManagerTest extends DrupalUnitTestBase {

  /**
   * A list of modules to install for this test.
   *
   * @var array
   */
  public static $modules = array('language', 'locale', 'locale_test');

  /**
   * Tests hasTranslation().
   */
  public function testHasTranslation() {
    $this->installConfig(array('locale_test'));
    $locale_config_manager = \Drupal::service('locale.config.typed');

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    $result = $locale_config_manager->hasTranslation('locale_test.no_translation', $language);
    $this->assertFalse($result, 'There is no translation for locale_test.no_translation configuration.');

    $result = $locale_config_manager->hasTranslation('locale_test.translation', $language);
    $this->assertTrue($result, 'There is a translation for locale_test.translation configuration.');
  }
}
