<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Config\ConfigCollectionEvents;
use Drupal\language\Config\LanguageConfigOverrideEvents;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\KernelTests\KernelTestBase;

// cspell:ignore deutsch

/**
 * Ensures the language config overrides can be installed.
 *
 * @group language
 */
class LanguageConfigOverrideInstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'config_events_test', 'language_events_test'];

  /**
   * Tests the configuration events are not fired during install of overrides.
   */
  public function testLanguageConfigOverrideInstall(): void {
    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('de')->save();
    // Need to enable test module after creating the language otherwise saving
    // the language will install the configuration.
    $this->enableModules(['language_config_override_test']);
    \Drupal::state()->set('config_events_test.event', FALSE);
    \Drupal::state()->set('language_events_test.all_events', []);
    $this->installConfig(['language_config_override_test']);

    // Ensure the save-in-collection event is triggered when saving data in
    // config collections during an install.
    $event_recorder = \Drupal::state()->get('config_events_test.event', FALSE);
    $this->assertSame([
      'event_name' => ConfigCollectionEvents::SAVE_IN_COLLECTION,
      'current_config_data' => ['name' => 'Deutsch'],
      'original_config_data' => [],
      'raw_config_data' => ['name' => 'Deutsch'],
    ], $event_recorder);
    $config = \Drupal::service('language.config_factory_override')->getOverride('de', 'language_config_override_test.settings');
    $this->assertEquals('Deutsch', $config->get('name'));

    // Ensure the save override event is triggered when saving overrides during
    // an install.
    $event_recorder = \Drupal::state()->get('language_events_test.all_events', []);
    $this->assertArrayHasKey(LanguageConfigOverrideEvents::SAVE_OVERRIDE, $event_recorder);
    $this->assertArrayHasKey('language_config_override_test.settings', $event_recorder[LanguageConfigOverrideEvents::SAVE_OVERRIDE]);
    $this->assertSame([
      'event_name' => LanguageConfigOverrideEvents::SAVE_OVERRIDE,
      'current_override_data' => ['name' => 'Deutsch'],
      'original_override_data' => [],
    ], $event_recorder['language.save_override']['language_config_override_test.settings'][0]);

    // Test events during uninstall.
    \Drupal::state()->set('config_events_test.all_events', []);
    \Drupal::state()->set('language_events_test.all_events', []);
    $this->container->get('module_installer')->uninstall(['language_config_override_test']);

    // Ensure the delete-in-collection event is triggered when deleting data in
    // config collections during an uninstall.
    $event_recorder = \Drupal::state()->get('config_events_test.all_events', []);
    $this->assertArrayHasKey(ConfigCollectionEvents::DELETE_IN_COLLECTION, $event_recorder);
    $this->assertArrayHasKey('language_config_override_test.settings', $event_recorder[ConfigCollectionEvents::DELETE_IN_COLLECTION]);
    $this->assertSame([
      'event_name' => ConfigCollectionEvents::DELETE_IN_COLLECTION,
      'current_config_data' => [],
      'original_config_data' => ['name' => 'Deutsch'],
      'raw_config_data' => [],
    ], $event_recorder[ConfigCollectionEvents::DELETE_IN_COLLECTION]['language_config_override_test.settings'][0]);

    // Ensure the delete override event is triggered when deleting overrides
    // during an uninstall.
    $event_recorder = \Drupal::state()->get('language_events_test.all_events', []);
    $this->assertArrayHasKey(LanguageConfigOverrideEvents::DELETE_OVERRIDE, $event_recorder);
    $this->assertArrayHasKey('language_config_override_test.settings', $event_recorder[LanguageConfigOverrideEvents::DELETE_OVERRIDE]);
    $this->assertSame([
      'event_name' => LanguageConfigOverrideEvents::DELETE_OVERRIDE,
      'current_override_data' => [],
      'original_override_data' => ['name' => 'Deutsch'],
    ], $event_recorder['language.delete_override']['language_config_override_test.settings'][0]);
  }

}
