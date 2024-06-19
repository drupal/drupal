<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\StringInterface;
use Drupal\locale\TranslationString;

/**
 * Tests that shipped configuration translations are updated correctly.
 *
 * @group locale
 */
class LocaleConfigSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'locale', 'system', 'locale_test'];

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
   * @var \Drupal\locale\StringStorageInterface
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
  protected function setUp(): void {
    parent::setUp();

    $this->setUpDefaultLanguage();

    $this->installSchema('locale', ['locales_source', 'locales_target', 'locales_location']);

    $this->setupLanguages();

    $this->installConfig(['locale_test']);
    // Simulate this hook invoked which would happen if in a non-kernel test
    // or normal environment.
    // @see locale_modules_installed()
    // @see locale_system_update()
    locale_system_set_config_langcodes();
    $langcodes = array_keys(\Drupal::languageManager()->getLanguages());
    $locale_config_manager = \Drupal::service('locale.config_manager');
    $names = $locale_config_manager->getComponentNames();
    $locale_config_manager->updateConfigTranslations($names, $langcodes);

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
    $this->setUpTranslation('locale_test.translation_multiple', 'test', 'English test', 'German test', 'de');
  }

  /**
   * Tests creating translations of shipped configuration.
   */
  public function testCreateTranslation(): void {
    $config_name = 'locale_test.no_translation';

    $this->saveLanguageOverride($config_name, 'test', 'Test (German)', 'de');
    $this->assertTranslation($config_name, 'Test (German)', 'de');
  }

  /**
   * Tests creating translations configuration with multi value settings.
   */
  public function testCreateTranslationMultiValue(): void {
    $config_name = 'locale_test.translation_multiple';

    $this->saveLanguageOverride($config_name, 'test_multiple', ['string' => 'String (German)', 'another_string' => 'Another string (German)'], 'de');
    $this->saveLanguageOverride($config_name, 'test_after_multiple', ['string' => 'After string (German)', 'another_string' => 'After another string (German)'], 'de');
    $strings = $this->stringStorage->getTranslations([
      'type' => 'configuration',
      'name' => $config_name,
      'language' => 'de',
      'translated' => TRUE,
    ]);
    $this->assertCount(5, $strings);
  }

  /**
   * Tests importing community translations of shipped configuration.
   */
  public function testLocaleCreateTranslation(): void {
    $config_name = 'locale_test.no_translation';

    $this->saveLocaleTranslationData($config_name, 'test', 'Test', 'Test (German)', 'de');
    $this->assertTranslation($config_name, 'Test (German)', 'de', FALSE);
  }

  /**
   * Tests updating translations of shipped configuration.
   */
  public function testUpdateTranslation(): void {
    $config_name = 'locale_test.translation';

    $this->saveLanguageOverride($config_name, 'test', 'Updated German test', 'de');
    $this->assertTranslation($config_name, 'Updated German test', 'de');
  }

  /**
   * Tests updating community translations of shipped configuration.
   */
  public function testLocaleUpdateTranslation(): void {
    $config_name = 'locale_test.translation';

    $this->saveLocaleTranslationData($config_name, 'test', 'English test', 'Updated German test', 'de');
    $this->assertTranslation($config_name, 'Updated German test', 'de', FALSE);
  }

  /**
   * Tests deleting translations of shipped configuration.
   */
  public function testDeleteTranslation(): void {
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
  public function testLocaleDeleteTranslation(): void {
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
    $this->localeConfigManager->updateConfigTranslations([$config_name], [$langcode]);
    $this->assertNoConfigOverride($config_name, $key);
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
    $this->localeConfigManager->updateConfigTranslations([$config_name], [$langcode]);

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
   * @param string|array $value
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
    $this->localeConfigManager->updateConfigTranslations([$config_name], [$langcode]);
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

    $this->assertNoConfigOverride($config_name, $key);
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
    $this->localeConfigManager->updateConfigTranslations([$config_name], [$langcode]);
    $this->configFactory->reset($config_name);

    $this->assertNoConfigOverride($config_name, $key);
  }

  /**
   * Ensures configuration override is not present anymore.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $langcode
   *   The language code.
   *
   * @internal
   */
  protected function assertNoConfigOverride(string $config_name, string $langcode): void {
    $config_langcode = $this->configFactory->getEditable($config_name)->get('langcode');
    $override = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
    $this->assertNotEquals($langcode, $config_langcode);
    $this->assertTrue($override->isNew());
  }

  /**
   * Ensures configuration was saved correctly.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $key
   *   The configuration key.
   * @param string|array $value
   *   The configuration value.
   * @param string $langcode
   *   The language code.
   *
   * @internal
   */
  protected function assertConfigOverride(string $config_name, string $key, $value, string $langcode): void {
    $config_langcode = $this->configFactory->getEditable($config_name)->get('langcode');
    $override = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
    $this->assertNotEquals($langcode, $config_langcode);
    $this->assertEquals($value, $override->get($key));
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
   * @internal
   */
  protected function assertActiveConfig(string $config_name, string $key, string $value, string $langcode): void {
    $config = $this->configFactory->getEditable($config_name);
    $this->assertEquals($langcode, $config->get('langcode'));
    $this->assertSame($value, $config->get($key));
  }

  /**
   * Ensures no translation exists.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string $langcode
   *   The language code.
   *
   * @internal
   */
  protected function assertNoTranslation(string $config_name, string $langcode): void {
    $strings = $this->stringStorage->getTranslations([
      'type' => 'configuration',
      'name' => $config_name,
      'language' => $langcode,
      'translated' => TRUE,
    ]);
    $this->assertSame([], $strings);
  }

  /**
   * Asserts if a specific translation exists and its customization status.
   *
   * @param string $config_name
   *   The configuration name.
   * @param string|array $translation
   *   The translation.
   * @param string $langcode
   *   The language code.
   * @param bool $customized
   *   (optional) Asserts if the translation is customized or not.
   *
   * @internal
   */
  protected function assertTranslation(string $config_name, $translation, string $langcode, bool $customized = TRUE): void {
    // Make sure a string exists.
    $strings = $this->stringStorage->getTranslations([
      'type' => 'configuration',
      'name' => $config_name,
      'language' => $langcode,
      'translated' => TRUE,
    ]);
    $this->assertCount(1, $strings);
    $string = reset($strings);
    $this->assertInstanceOf(StringInterface::class, $string);
    /** @var \Drupal\locale\StringInterface $string */
    $this->assertSame($translation, $string->getString());
    $this->assertTrue($string->isTranslation());
    $this->assertInstanceOf(TranslationString::class, $string);
    /** @var \Drupal\locale\TranslationString $string */
    // Make sure the string is marked as customized so that it does not get
    // overridden when the string translations are updated.
    $this->assertEquals($customized, (bool) $string->customized);
  }

}
