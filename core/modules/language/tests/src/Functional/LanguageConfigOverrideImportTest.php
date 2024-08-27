<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Config\ConfigCollectionEvents;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures the language config overrides can be synchronized.
 *
 * @group language
 */
class LanguageConfigOverrideImportTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'config',
    'locale',
    'config_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that language can be enabled and overrides are created during a sync.
   */
  public function testConfigOverrideImport(): void {
    ConfigurableLanguage::createFromLangcode('fr')->save();
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = \Drupal::service('config.storage.sync');
    $this->copyConfig(\Drupal::service('config.storage'), $sync);

    // Uninstall the language module and its dependencies so we can test
    // enabling the language module and creating overrides at the same time
    // during a configuration synchronization.
    \Drupal::service('module_installer')->uninstall(['language']);
    // Ensure that the current site has no overrides registered to the
    // ConfigFactory.
    $this->rebuildContainer();

    /** @var \Drupal\Core\Config\StorageInterface $override_sync */
    $override_sync = $sync->createCollection('language.fr');
    // Create some overrides in sync.
    $override_sync->write('system.site', ['name' => 'FR default site name']);
    $override_sync->write('system.maintenance', ['message' => 'FR message: @site is currently under maintenance. We should be back shortly. Thank you for your patience']);

    $this->configImporter()->import();
    $this->rebuildContainer();

    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');
    $this->assertEquals('FR default site name', $override->get('name'));
    $this->drupalGet('fr');
    $this->assertSession()->pageTextContains('FR default site name');
    $this->drupalLogin($this->drupalCreateUser([
      'translate configuration',
    ]));
    $this->drupalGet('admin/config/development/maintenance/translate/fr/edit');
    $this->assertSession()->pageTextContains('FR message: @site is currently under maintenance. We should be back shortly. Thank you for your patience');
  }

  /**
   * Tests that configuration events are not fired during a sync of overrides.
   */
  public function testConfigOverrideImportEvents(): void {
    // Enable the config_events_test module so we can record events occurring.
    \Drupal::service('module_installer')->install(['config_events_test']);
    $this->rebuildContainer();

    ConfigurableLanguage::createFromLangcode('fr')->save();

    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = \Drupal::service('config.storage.sync');
    $this->copyConfig(\Drupal::service('config.storage'), $sync);

    /** @var \Drupal\Core\Config\StorageInterface $override_sync */
    $override_sync = $sync->createCollection('language.fr');
    // Create some overrides in sync.
    $override_sync->write('system.site', ['name' => 'FR default site name']);
    \Drupal::state()->set('config_events_test.event', FALSE);

    $this->configImporter()->import();
    $this->rebuildContainer();

    // Test that no config save event has been fired during the import because
    // language configuration overrides do not fire events.
    $event_recorder = \Drupal::state()->get('config_events_test.event', FALSE);
    $this->assertSame([
      'event_name' => ConfigCollectionEvents::SAVE_IN_COLLECTION,
      'current_config_data' => ['name' => 'FR default site name'],
      'original_config_data' => [],
      'raw_config_data' => ['name' => 'FR default site name'],
    ], $event_recorder);

    $this->drupalGet('fr');
    $this->assertSession()->pageTextContains('FR default site name');
  }

}
