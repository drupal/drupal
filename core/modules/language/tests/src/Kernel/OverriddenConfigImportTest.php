<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests importing of config with language overrides.
 *
 * @group language
 */
class OverriddenConfigImportTest extends KernelTestBase {

  /**
   * Config Importer object used for testing.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage')
    );
    $this->configImporter = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme')
    );
  }

  /**
   * Tests importing overridden config alongside config in the default language.
   */
  public function testConfigImportUpdates(): void {
    $storage = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');

    // Make a change to the site configuration in the default collection.
    $data = $storage->read('system.site');
    $data['name'] = 'English site name';
    $sync->write('system.site', $data);

    // Also make a change to the same config object, but using a language
    // override.
    /** @var \Drupal\Core\Config\StorageInterface $overridden_sync */
    $overridden_sync = $sync->createCollection('language.fr');
    $overridden_sync->write('system.site', ['name' => 'French site name']);

    // Before we start the import, the change to the site name should not be
    // present. This action also primes the cache in the config factory so that
    // we can test whether the cached data is correctly updated.
    $config = $this->config('system.site');
    $this->assertNotEquals('English site name', $config->getRawData()['name']);

    // Before the import is started the site name should not yet be overridden.
    $this->assertFalse($config->hasOverrides());
    $override = $language_manager->getLanguageConfigOverride('fr', 'system.site');
    $this->assertTrue($override->isNew());

    // Start the import of the new configuration.
    $this->configImporter->reset()->import();

    // Verify the new site name in the default language.
    $config = $this->config('system.site')->getRawData();
    $this->assertEquals('English site name', $config['name']);

    // Verify the overridden site name.
    $override = $language_manager->getLanguageConfigOverride('fr', 'system.site');
    $this->assertEquals('French site name', $override->get('name'));
  }

}
