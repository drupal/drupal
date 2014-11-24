<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigSubscriberTest.
 */

namespace Drupal\locale\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
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
  public static $modules = ['language', 'locale', 'locale_test'];

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
   * The language code used in this test.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->languageManager = $this->container->get('language_manager');
    $this->configFactory = $this->container->get('config.factory');
    $this->stringStorage = $this->container->get('locale.storage');
    $this->localeConfigManager = $this->container->get('locale.config.typed');

    $this->installSchema('locale', ['locales_source', 'locales_target', 'locales_location']);

    $this->installConfig(['locale_test']);
    ConfigurableLanguage::createFromLangcode($this->langcode)->save();
  }

  /**
   * Tests creating translations of shipped configuration.
   */
  public function testCreateTranslation() {
    $config_name = 'locale_test.no_translation';

    $this->setUpNoTranslation($config_name, 'test', 'Test');
    $this->saveLanguageOverride($config_name, 'test', 'Test (German)');
    $this->assertTranslation($config_name, 'Test (German)');
  }

  /**
   * Tests importing community translations of shipped configuration.
   */
  public function testLocaleCreateTranslation() {
    $config_name = 'locale_test.no_translation';

    $this->setUpNoTranslation($config_name, 'test', 'Test');
    $this->saveLocaleTranslationData($config_name, 'test', 'Test (German)');
    $this->assertTranslation($config_name, 'Test (German)', FALSE);
  }

  /**
   * Tests updating translations of shipped configuration.
   */
  public function testUpdateTranslation() {
    $config_name = 'locale_test.translation';

    $this->setUpTranslation($config_name, 'test', 'English test', 'German test');
    $this->saveLanguageOverride($config_name, 'test', 'Updated German test');
    $this->assertTranslation($config_name, 'Updated German test');
  }

  /**
   * Tests updating community translations of shipped configuration.
   */
  public function testLocaleUpdateTranslation() {
    $config_name = 'locale_test.translation';

    $this->setUpTranslation($config_name, 'test', 'English test', 'German test');
    $this->saveLocaleTranslationData($config_name, 'test', 'Updated German test');
    $this->assertTranslation($config_name, 'Updated German test', FALSE);
  }

  /**
   * Tests deleting translations of shipped configuration.
   */
  public function testDeleteTranslation() {
    $config_name = 'locale_test.translation';

    $this->setUpTranslation($config_name, 'test', 'English test', 'German test');
    $this->deleteLanguageOverride($config_name, 'test', 'English test');
    // Instead of deleting the translation, we need to keep a translation with
    // the source value and mark it as customized to prevent the deletion being
    // reverted by importing community translations.
    $this->assertTranslation($config_name, 'English test');
  }

  /**
   * Tests deleting community translations of shipped configuration.
   */
  public function testLocaleDeleteTranslation() {
    $config_name = 'locale_test.translation';

    $this->setUpTranslation($config_name, 'test', 'English test', 'German test');
    $this->deleteLocaleTranslationData($config_name, 'test', 'English test');
    $this->assertNoTranslation($config_name, 'English test', FALSE);
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
   */
  protected function setUpNoTranslation($config_name, $key, $source) {
    // Add a source string with the configuration name as a location. This gets
    // called from locale_config_update_multiple() normally.
    $this->localeConfigManager->translateString($config_name, $this->langcode, $source, '');
    $this->languageManager
      ->setConfigOverrideLanguage(ConfigurableLanguage::load($this->langcode));

    $this->assertConfigValue($config_name, $key, $source);
    $this->assertNoTranslation($config_name);
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
   */
  protected function setUpTranslation($config_name, $key, $source, $translation) {
    // Create source and translation strings for the configuration value and add
    // the configuration name as a location. This would be performed by
    // locale_translate_batch_import() and locale_config_update_multiple()
    // normally.
    $source_object = $this->stringStorage->createString([
      'source' => $source,
      'context' => '',
    ])->save();
    $this->stringStorage->createTranslation([
      'lid' => $source_object->getId(),
      'language' => $this->langcode,
      'translation' => $translation,
    ])->save();
    $this->localeConfigManager->translateString($config_name, $this->langcode, $source, '');
    $this->languageManager
      ->setConfigOverrideLanguage(ConfigurableLanguage::load($this->langcode));

    $this->assertConfigValue($config_name, $key, $translation);
    $this->assertTranslation($config_name, $translation, FALSE);
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
   */
  protected function saveLanguageOverride($config_name, $key, $value) {
    $translation_override = $this->languageManager
      ->getLanguageConfigOverride($this->langcode, $config_name);
    $translation_override
      ->set($key, $value)
      ->save();
    $this->configFactory->reset($config_name);

    $this->assertConfigValue($config_name, $key, $value);
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
   * @param string $value
   *   The configuration value to save.
   */
  protected function saveLocaleTranslationData($config_name, $key, $value) {
    $this->localeConfigManager
      ->saveTranslationData($config_name, $this->langcode, [$key => $value]);
    $this->configFactory->reset($config_name);

    $this->assertConfigValue($config_name, $key, $value);
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
   */
  protected function deleteLanguageOverride($config_name, $key, $source_value) {
    $translation_override = $this->languageManager
      ->getLanguageConfigOverride($this->langcode, $config_name);
    $translation_override
      ->clear($key)
      ->save();
    $this->configFactory->reset($config_name);

    $this->assertConfigValue($config_name, $key, $source_value);
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
   */
  protected function deleteLocaleTranslationData($config_name, $key, $source_value) {
    $this->localeConfigManager->deleteTranslationData($config_name, $this->langcode);
    $this->configFactory->reset($config_name);

    $this->assertConfigValue($config_name, $key, $source_value);
  }

  /**
   * Ensures configuration was saved correctly.
   *
   * @param $config_name
   *   The configuration name.
   * @param $key
   *   The configuration key.
   * @param $value
   *   The configuration value.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertConfigValue($config_name, $key, $value) {
    // Make sure the configuration was translated correctly.
    $translation_config = $this->configFactory->get($config_name);
    $passed = $this->assertIdentical($value, $translation_config->get($key));

    // Make sure the override state of the configuration factory was not
    // modified.
    return $passed && $this->assertIdentical(TRUE, $this->configFactory->getOverrideState());
  }

  /**
   * Ensures no translation exists.
   *
   * @param string $config_name
   *   The configuration name.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoTranslation($config_name) {
    $strings = $this->stringStorage->getTranslations([
      'type' => 'configuration',
      'name' => $config_name,
      'language' => $this->langcode,
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
   * @param bool $customized
   *   Whether or not the string should be asserted to be customized or not
   *   customized.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertTranslation($config_name, $translation, $customized = TRUE) {
    // Make sure a string exists.
    $strings = $this->stringStorage->getTranslations([
      'type' => 'configuration',
      'name' => $config_name,
      'language' => $this->langcode,
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
