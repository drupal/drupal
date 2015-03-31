<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigManagerTest.
 */

namespace Drupal\locale\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests that the locale config manager operates correctly.
 *
 * @group locale
 */
class LocaleConfigManagerTest extends KernelTestBase {

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
    $this->installSchema('locale', array('locales_location', 'locales_source', 'locales_target'));
    $this->installConfig(array('locale_test'));
    $locale_config_manager = \Drupal::service('locale.config_manager');

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    $result = $locale_config_manager->hasTranslation('locale_test.no_translation', $language->getId());
    $this->assertFalse($result, 'There is no translation for locale_test.no_translation configuration.');

    $result = $locale_config_manager->hasTranslation('locale_test.translation', $language->getId());
    $this->assertTrue($result, 'There is a translation for locale_test.translation configuration.');
  }

  /**
   * Tests getStringTranslation().
   */
  public function testGetStringTranslation() {
    $this->installSchema('locale', array('locales_location', 'locales_source', 'locales_target'));
    $this->installConfig(array('locale_test'));

    $locale_config_manager = \Drupal::service('locale.config_manager');

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    $translation_before = $locale_config_manager->getStringTranslation('locale_test.no_translation', $language->getId(), 'Test', '');
    $this->assertTrue($translation_before->isNew());
    $translation_before->setString('translation')->save();

    $translation_after = $locale_config_manager->getStringTranslation('locale_test.no_translation', $language->getId(), 'Test', '');
    $this->assertFalse($translation_after->isNew());
    $translation_after->setString('updated_translation')->save();
  }
}
