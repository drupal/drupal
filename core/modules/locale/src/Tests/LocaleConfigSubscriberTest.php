<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigSubscriberTest.
 */

namespace Drupal\locale\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\Locale;
use Drupal\locale\StringInterface;
use Drupal\locale\TranslationString;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests that shipped configuration translations are updated correctly.
 *
 * @group locale
 */
class LocaleConfigSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'locale', 'system'];

  /**
   * The configurable language manager used in this test.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory used in this test.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The string storage used in this test.
   *
   * @var \Drupal\locale\StringStorageInterface;
   */
  protected $stringStorage;

  /**
   * The locale configuration manager used in this test.
   *
   * @var \Drupal\locale\LocaleConfigManager
   */
  protected $localeConfigManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpDefaultLanguage();

    $this->installSchema('locale', ['locales_source', 'locales_target', 'locales_location']);
    $this->installSchema('system', ['queue']);

    $this->setupLanguages();

    $this->enableModules(['locale_test']);
    $this->installConfig(['locale_test']);
    // Simulate this hook invoked which would happen if in a non-kernel test
    // or normal environment.
    // @see locale_modules_installed()
    // @see locale_system_update()
    locale_system_set_config_langcodes();
    $langcodes = array_keys(\Drupal::languageManager()->getLanguages());
    $names = \Drupal\locale\Locale::config()->getComponentNames();
    Locale::config()->updateConfigTranslations($names, $langcodes);

    $this->configFactory = $this->container->get('config.factory');
    $this->stringStorage = $this->container->get('locale.storage');
    $this->localeConfigManager = $this->container->get('locale.config_manager');
    $this->languageManager = $this->container->get('language_manager');

    $this->setUpLocale();
  }

  /**
   * Sets up default language for this test.
   */
  protected function setUpDefaultLanguage() {
    // Keep the default English.
  }

  /**
   * Sets up languages needed for this test.
   */
  protected function setUpLanguages() {
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Sets up the locale storage strings to be in line with configuration.
   */
  protected function setUpLocale() {
    // Set up the locale database the same way we have in the config samples.
    $this->setUpNoTranslation('locale_test.no_translation', 'test', 'Test', 'de');
    $this->setUpTranslation('locale_test.translation', 'test', 'English test', 'German test', 'de');
  }

  /**
   * Tests creating translations of shipped configuration.
   */
  public function testCreateTranslation() {
    $config_name = 'locale_test.no_translation';

    $this->saveLanguageOverride($config_name, 'test', 'Test (German)', 'de');
    $this->assertTranslation($config_name, 'Test (German)', 'de');
  }

  /**
   * Tests importing community translations of shipped configuration.
   */
  public function testLocaleCreateTranslation() {
    $config_name = 'locale_test.no_translation';

    $this->saveLocaleTranslationData($config_name, 'test', 'Test', 'Test (German)', 'de');
    $this->assertTranslation($config_name, 'Test (German)', 'de', FALSE);
  }

  /**
   * Tests updating translations of shipped configuration.
   */
  public function testUpdateTranslation() {
    $config_name = 'locale_test.translation';

    $this->saveLanguageOverride($config_name, 'test', 'Updated German test', 'de');
    $this->assertTranslation($config_name, 'Updated German test', 'de');
  }

  /**
   * Tests updating community translations of shipped configuration.
   */
  public function testLocaleUpdateTranslation() {
    $config_name = 'locale_test.translation';

    $this->saveLocaleTranslationData($config_name, 'test', 'English test', 'Updated German test', 'de');
    $this->assertTranslation($config_name, 'Updated German test', 'de', FALSE);
  }

  /**
   * Tests deleting translations of shipped configuration.
   */
  public function testDeleteTranslation() {
    $config_name = 'locale_test.translation';

    $this->deleteLanguageOverride($config_name, 'test', 'English test', 'de');
    // Instead of deleting the translation, we need to keep a translation with
    // the source value and mark it as customized to prevent the deletion being
    // reverted by importing community translations.
    $this->assertTranslation($config_name, 'English test', 'de');
  }

  /**
   * Tests deleting community translations of shipped configuration.
   */
  public function testLocaleDeleteTranslation() {
    $config_name = 'locale_test.translation';

    $this->deleteLocaleTranslationData($config_name, 'test', 'English test', 'de');
    $this->assertNoTranslation($config_name, 'de');
  }

  /**
   * Sets up a configuration string without a translation.
   *
   * The actual configuration is already available by installing locale_test
   * module, as it is done in LocaleConfigSubscriberTest::setUp(). This sets up
   * the necessary source string and verifies that everything is as expected to
   * avoid false positives.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $source
   *   The source string.
   * @param string $langcode
   *   The language code.
   */
  protected function setUpNoTranslation($config_name, $key, $source, $langcode) {
    $this->localeConfigManager->updateConfigTranslations(array($config_name), array($langcode));
    $this->assertNoConfigOverride($config_name, $key, $source, $langcode);
    $this->assertNoTranslation($config_name, $langcode);
  }


  /**
   * Sets up a configuration string with a translation.
   *
   * The actual configuration is already available by installing locale_test
   * module, as it is done in LocaleConfigSubscriberTest::setUp(). This sets up
   * the necessary source and translation strings and verifies that everything
   * is as expected to avoid false positives.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $source
   *   The source string.
   * @param string $translation
   *   The translation string.
   * @param string $langcode
   *   The language code.
   * @param bool $is_active
   *   Whether the update will affect the active configuration.
   */
  protected function setUpTranslation($config_name, $key, $source, $translation, $langcode, $is_active = FALSE) {
    // Create source and translation strings for the configuration value and add
    // the configuration name as a location. This would be performed by
    // locale_translate_batch_import() invoking
    // LocaleConfigManager::updateConfigTranslations() normally.
    $this->localeConfigManager->reset();
    $this->localeConfigManager
      ->getStringTranslation($config_name, $langcode, $source, '')
      ->setString($translation)
      ->setCustomized(FALSE)
      ->save();
    $this->configFactory->reset($config_name);
    $this->localeConfigManager->reset();
    $this->localeConfigManager->updateConfigTranslations(array($config_name), array($langcode));

    if ($is_active) {
      $this->assertActiveConfig($config_name, $key, $translation, $langcode);
    }
    else {
      $this->assertConfigOverride($config_name, $key, $translation, $langcode);
    }
    $this->assertTranslation($config_name, $translation, $langcode, FALSE);
  }

  /**
   * Saves a language override.
   *
   * This will invoke LocaleConfigSubscriber through the event dispatcher. To
   * make sure the configuration was persisted correctly, the configuration
   * value is checked. Because LocaleConfigSubscriber temporarily disables the
   * override state of the configuration factory we check that the correct value
   * is restored afterwards.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $value
   *   The configuration value to save.
   * @param string $langcode
   *   The language code.
   */
  protected function saveLanguageOverride($config_name, $key, $value, $langcode) {
    $translation_override = $this->languageManager
      ->getLanguageConfigOverride($langcode, $config_name);
    $translation_override
      ->set($key, $value)
      ->save();
    $this->configFactory->reset($config_name);

    $this->assertConfigOverride($config_name, $key, $value, $langcode);
  }

  /**
   * Saves translation data from locale module.
   *
   * This will invoke LocaleConfigSubscriber through the event dispatcher. To
   * make sure the configuration was persisted correctly, the configuration
   * value is checked. Because LocaleConfigSubscriber temporarily disables the
   * override state of the configuration factory we check that the correct value
   * is restored afterwards.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $source
   *   The source string.
   * @param string $translation
   *   The translation string to save.
   * @param string $langcode
   *   The language code.
   * @param bool $is_active
   *   Whether the update will affect the active configuration.
   */
  protected function saveLocaleTranslationData($config_name, $key, $source, $translation, $langcode, $is_active = FALSE) {
    $this->localeConfigManager->reset();
    $this->localeConfigManager
      ->getStringTranslation($config_name, $langcode, $source, '')
      ->setString($translation)
      ->save();
    $this->localeConfigManager->reset();
    $this->localeConfigManager->updateConfigTranslations(array($config_name), array($langcode));
    $this->configFactory->reset($config_name);

    if ($is_active) {
      $this->assertActiveConfig($config_name, $key, $translation, $langcode);
    }
    else {
      $this->assertConfigOverride($config_name, $key, $translation, $langcode);
    }
  }

  /**
   * Deletes a language override.
   *
   * This will invoke LocaleConfigSubscriber through the event dispatcher. To
   * make sure the configuration was persisted correctly, the configuration
   * value is checked. Because LocaleConfigSubscriber temporarily disables the
   * override state of the configuration factory we check that the correct value
   * is restored afterwards.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $source_value
   *   The source configuration value to verify the correct value is returned
   *   from the configuration factory after the deletion.
   * @param string $langcode
   *   The language code.
   */
  protected function deleteLanguageOverride($config_name, $key, $source_value, $langcode) {
    $translation_override = $this->languageManager
      ->getLanguageConfigOverride($langcode, $config_name);
    $translation_override
      ->clear($key)
      ->save();
    $this->configFactory->reset($config_name);

    $this->assertNoConfigOverride($config_name, $key, $source_value, $langcode);
  }

  /**
   * Deletes translation data from locale module.
   *
   * This will invoke LocaleConfigSubscriber through the event dispatcher. To
   * make sure the configuration was persisted correctly, the configuration
   * value is checked. Because LocaleConfigSubscriber temporarily disables the
   * override state of the configuration factory we check that the correct value
   * is restored afterwards.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $source_value
   *   The source configuration value to verify the correct value is returned
   *   from the configuration factory after the deletion.
   * @param string $langcode
   *   The language code.
   */
  protected function deleteLocaleTranslationData($config_name, $key, $source_value, $langcode) {
    $this->localeConfigManager
      ->getStringTranslation($config_name, $langcode, $source_value, '')
      ->delete();
    $this->localeConfigManager->reset();
    $this->localeConfigManager->updateConfigTranslations(array($config_name), array($langcode));
    $this->configFactory->reset($config_name);

    $this->assertNoConfigOverride($config_name, $key, $source_value, $langcode);
  }

  /**
   * Ensures configuration override is not present anymore.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoConfigOverride($config_name, $langcode) {
    $config_langcode = $this->configFactory->getEditable($config_name)->get('langcode');
    $override = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
    return $this->assertNotEqual($config_langcode, $langcode) && $this->assertEqual($override->isNew(), TRUE);
  }

  /**
   * Ensures configuration was saved correctly.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $value
   *   The configuration value.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertConfigOverride($config_name, $key, $value, $langcode) {
    $config_langcode = $this->configFactory->getEditable($config_name)->get('langcode');
    $override = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
    return $this->assertNotEqual($config_langcode, $langcode) && $this->assertEqual($override->get($key), $value);
  }

  /**
   * Ensures configuration was saved correctly.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string $value
   *   The configuration value.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertActiveConfig($config_name, $key, $value, $langcode) {
    $config = $this->configFactory->getEditable($config_name);
    return
      $this->assertEqual($config->get('langcode'), $langcode) &&
      $this->assertIdentical($config->get($key), $value);
  }

  /**
   * Ensures no translation exists.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoTranslation($config_name, $langcode) {
    $strings = $this->stringStorage->getTranslations([
      'type' => 'configuration',
      'name' => $config_name,
      'language' => $langcode,
      'translated' => TRUE,
    ]);
    return $this->assertIdentical([], $strings);
  }

  /**
   * Ensures a translation exists and is marked as customized.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $translation
   *   The translation.
   * @param string $langcode
   *   The language code.
   * @param bool $customized
   *   Whether or not the string should be asserted to be customized or not
   *   customized.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertTranslation($config_name, $translation, $langcode, $customized = TRUE) {
    // Make sure a string exists.
    $strings = $this->stringStorage->getTranslations([
      'type' => 'configuration',
      'name' => $config_name,
      'language' => $langcode,
      'translated' => TRUE,
    ]);
    $pass = $this->assertIdentical(1, count($strings));
    $string = reset($strings);
    if ($this->assertTrue($string instanceof StringInterface)) {
      /** @var \Drupal\locale\StringInterface $string */
      $pass = $pass && $this->assertIdentical($translation, $string->getString());
      $pass = $pass && $this->assertTrue($string->isTranslation());
      if ($this->assertTrue($string instanceof TranslationString)) {
        /** @var \Drupal\locale\TranslationString $string */
        // Make sure the string is marked as customized so that it does not get
        // overridden when the string translations are updated.
        return $pass && $this->assertEqual($customized, $string->customized);
      }
    }
    return FALSE;
  }

}
