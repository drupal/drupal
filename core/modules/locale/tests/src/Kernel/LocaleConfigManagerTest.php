<?php

namespace Drupal\Tests\locale\Kernel;

use Drupal\block\Entity\Block;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\KernelTests\KernelTestBase;

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
  public static $modules = [
    'system',
    'language',
    'locale',
    'locale_test',
    'block',
  ];

  /**
   * This test creates simple config on the fly breaking schema checking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Tests hasTranslation().
   */
  public function testHasTranslation() {
    $this->installSchema('locale', ['locales_location', 'locales_source', 'locales_target']);
    $this->installConfig(['locale_test']);
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
    $this->installSchema('locale', ['locales_location', 'locales_source', 'locales_target']);
    $this->installConfig(['locale_test']);

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

  /**
   * Tests getDefaultConfigLangcode().
   */
  public function testGetDefaultConfigLangcode() {
    // Install the Language module's configuration so we can use the
    // module_installer service.
    $this->installConfig(['language']);
    $this->assertNull(\Drupal::service('locale.config_manager')->getDefaultConfigLangcode('locale_test_translate.settings'), 'Before installing a module the locale config manager can not access the shipped configuration.');
    \Drupal::service('module_installer')->install(['locale_test_translate']);
    $this->assertEqual('en', \Drupal::service('locale.config_manager')->getDefaultConfigLangcode('locale_test_translate.settings'), 'After installing a module the locale config manager can get the shipped configuration langcode.');

    $simple_config = \Drupal::configFactory()->getEditable('locale_test_translate.simple_config_extra');
    $simple_config->set('foo', 'bar')->save();
    $this->assertNull(\Drupal::service('locale.config_manager')->getDefaultConfigLangcode($simple_config->getName()), 'Simple config created through the API is not treated as shipped configuration.');

    $block = Block::create([
      'id' => 'test_default_config',
      'theme' => 'classy',
      'status' => TRUE,
      'region' => 'content',
      'plugin' => 'local_tasks_block',
      'settings' => [
        'id' => 'local_tasks_block',
        'label' => $this->randomMachineName(),
        'provider' => 'core',
        'label_display' => FALSE,
        'primary' => TRUE,
        'secondary' => TRUE,
      ],
    ]);
    $block->save();

    // Install the theme after creating the block as installing the theme will
    // install the block provided by the locale_test module.
    \Drupal::service('theme_installer')->install(['classy']);

    // The test_default_config block provided by the locale_test module will not
    // be installed because a block with the same ID already exists.
    $this->installConfig(['locale_test']);
    $this->assertNull(\Drupal::service('locale.config_manager')->getDefaultConfigLangcode('block.block.test_default_config'), 'The block.block.test_default_config is not shipped configuration.');
    // Delete the block so we can install the one provided by the locale_test
    // module.
    $block->delete();
    $this->installConfig(['locale_test']);
    $this->assertEqual('en', \Drupal::service('locale.config_manager')->getDefaultConfigLangcode('block.block.test_default_config'), 'The block.block.test_default_config is shipped configuration.');

    // Test the special case for configurable_language config entities.
    $fr_language = ConfigurableLanguage::createFromLangcode('fr');
    $fr_language->save();
    $this->assertEqual('en', \Drupal::service('locale.config_manager')->getDefaultConfigLangcode('language.entity.fr'), 'The language.entity.fr is treated as shipped configuration because it is a configurable_language config entity and in the standard language list.');
    $custom_language = ConfigurableLanguage::createFromLangcode('custom');
    $custom_language->save();
    $this->assertNull(\Drupal::service('locale.config_manager')->getDefaultConfigLangcode('language.entity.custom'), 'The language.entity.custom is not shipped configuration because it is not in the standard language list.');
  }

}
