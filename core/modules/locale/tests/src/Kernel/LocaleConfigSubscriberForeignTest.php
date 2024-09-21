<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests default configuration handling with a foreign default language.
 *
 * @group locale
 */
class LocaleConfigSubscriberForeignTest extends LocaleConfigSubscriberTest {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $language = Language::$defaultValues;
    $language['id'] = 'hu';
    $language['name'] = 'Hungarian';
    $container->setParameter('language.default_values', $language);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguages() {
    parent::setUpLanguages();
    ConfigurableLanguage::createFromLangcode('hu')->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLocale() {
    parent::setUpLocale();
    $this->setUpTranslation('locale_test.translation', 'test', 'English test', 'Hungarian test', 'hu', TRUE);
  }

  /**
   * Tests that the language of default configuration was updated.
   */
  public function testDefaultConfigLanguage(): void {
    $this->assertEquals('hu', $this->configFactory->getEditable('locale_test.no_translation')->get('langcode'));
    $this->assertEquals('hu', $this->configFactory->getEditable('locale_test.translation')->get('langcode'));
    $this->assertEquals('Hungarian test', $this->configFactory->getEditable('locale_test.translation')->get('test'));
  }

  /**
   * Tests creating translations of shipped configuration.
   */
  public function testCreateActiveTranslation(): void {
    $config_name = 'locale_test.no_translation';
    $this->saveLanguageActive($config_name, 'test', 'Test (Hungarian)', 'hu');
    $this->assertTranslation($config_name, 'Test (Hungarian)', 'hu');
  }

  /**
   * Tests importing community translations of shipped configuration.
   */
  public function testLocaleCreateActiveTranslation(): void {
    $config_name = 'locale_test.no_translation';
    $this->saveLocaleTranslationData($config_name, 'test', 'Test', 'Test (Hungarian)', 'hu', TRUE);
    $this->assertTranslation($config_name, 'Test (Hungarian)', 'hu', FALSE);
  }

  /**
   * Tests updating translations of shipped configuration.
   */
  public function testUpdateActiveTranslation(): void {
    $config_name = 'locale_test.translation';
    $this->saveLanguageActive($config_name, 'test', 'Updated Hungarian test', 'hu');
    $this->assertTranslation($config_name, 'Updated Hungarian test', 'hu');
  }

  /**
   * Tests updating community translations of shipped configuration.
   */
  public function testLocaleUpdateActiveTranslation(): void {
    $config_name = 'locale_test.translation';
    $this->saveLocaleTranslationData($config_name, 'test', 'English test', 'Updated Hungarian test', 'hu', TRUE);
    $this->assertTranslation($config_name, 'Updated Hungarian test', 'hu', FALSE);
  }

  /**
   * Tests deleting a translation override.
   */
  public function testDeleteTranslation(): void {
    $config_name = 'locale_test.translation';
    $this->deleteLanguageOverride($config_name, 'test', 'English test', 'de');
    // The German translation in this case will be forced to the Hungarian
    // source so its not overwritten with locale data later.
    $this->assertTranslation($config_name, 'Hungarian test', 'de');
  }

  /**
   * Tests deleting translations of shipped configuration.
   */
  public function testDeleteActiveTranslation(): void {
    $config_name = 'locale_test.translation';
    $this->configFactory->getEditable($config_name)->delete();
    // Deleting active configuration should not change the locale translation.
    $this->assertTranslation($config_name, 'Hungarian test', 'hu', FALSE);
  }

  /**
   * Tests deleting community translations of shipped configuration.
   */
  public function testLocaleDeleteActiveTranslation(): void {
    $config_name = 'locale_test.translation';
    $this->deleteLocaleTranslationData($config_name, 'test', 'English test', 'hu');
    // Deleting the locale translation should not change active config.
    $this->assertEquals('Hungarian test', $this->configFactory->getEditable($config_name)->get('test'));
  }

  /**
   * Tests that adding English creates a translation override.
   */
  public function testEnglish(): void {
    $config_name = 'locale_test.translation';
    ConfigurableLanguage::createFromLangcode('en')->save();
    // Adding a language on the UI would normally call updateConfigTranslations.
    $this->localeConfigManager->updateConfigTranslations([$config_name], ['en']);
    $this->assertConfigOverride($config_name, 'test', 'English test', 'en');

    $this->configFactory->getEditable('locale.settings')->set('translate_english', TRUE)->save();
    $this->saveLocaleTranslationData($config_name, 'test', 'English test', 'Updated English test', 'en');
    $this->assertTranslation($config_name, 'Updated English test', 'en', FALSE);

    $this->saveLanguageOverride($config_name, 'test', 'Updated English', 'en');
    $this->assertTranslation($config_name, 'Updated English', 'en');

    $this->deleteLocaleTranslationData($config_name, 'test', 'English test', 'en');
    $this->assertNoConfigOverride($config_name, 'en');
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
  protected function saveLanguageActive($config_name, $key, $value, $langcode) {
    $this
      ->configFactory
      ->getEditable($config_name)
      ->set($key, $value)
      ->save();
    $this->assertActiveConfig($config_name, $key, $value, $langcode);
  }

}
