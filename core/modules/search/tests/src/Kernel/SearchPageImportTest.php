<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search\Entity\SearchPage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests search page import.
 */
#[Group('search')]
#[RunTestsInSeparateProcesses]
class SearchPageImportTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search',
    'search_extra_type',
    'system',
  ];

  /**
   * Tests updating a search page during import.
   */
  public function testSearchPageImport(): void {
    $this->installConfig(['system']);
    $config_storage = $this->container->get('config.storage');
    // Ensure the 'system.site' config.
    $config_storage->write('system.site', ['uuid' => (new Php())->generate()]);
    $this->copyConfig($config_storage, $this->container->get('config.storage.sync'));

    // Create a test search page with a known label.
    $name = 'search.page.apple';
    $entity = SearchPage::create([
      'id' => 'apple',
      'label' => 'Apple search',
      'path' => 'apple',
      'plugin' => 'search_extra_type_search',
    ]);
    $entity->save();

    $this->checkSinglePluginConfigSync($entity, 'configuration', 'boost', 'bi');

    // Read the existing data, and prepare an altered version in sync.
    $custom_data = $original_data = $this->container->get('config.storage')->read($name);
    $custom_data['configuration']['boost'] = 'asdf';
    $this->assertConfigUpdateImport($name, $original_data, $custom_data);
  }

  /**
   * Tests that a single set of plugin config stays in sync.
   *
   * @param \Drupal\Core\Entity\EntityWithPluginCollectionInterface $entity
   *   The entity.
   * @param string $config_key
   *   Where the plugin config is stored.
   * @param string $setting_key
   *   The setting within the plugin config to change.
   * @param mixed $expected
   *   The expected default value of the plugin config setting.
   */
  protected function checkSinglePluginConfigSync(EntityWithPluginCollectionInterface $entity, $config_key, $setting_key, $expected): void {
    $plugin_collection = $entity->getPluginCollections()[$config_key];
    $settings = $entity->get($config_key);

    // Ensure the default config exists.
    $this->assertSame($expected, $settings[$setting_key]);

    // Change the plugin config by setting it on the entity.
    $settings[$setting_key] = $this->randomString();
    $entity->set($config_key, $settings);
    $entity->save();
    $this->assertSame($settings, $entity->get($config_key));
    $this->assertSame($settings, $plugin_collection->getConfiguration());

    // Change the plugin config by setting it on the plugin.
    $settings[$setting_key] = $this->randomString();
    $plugin_collection->setConfiguration($settings);
    $entity->save();
    $this->assertSame($settings, $entity->get($config_key));
    $this->assertSame($settings, $plugin_collection->getConfiguration());
  }

  /**
   * Asserts that config entities are updated during import.
   *
   * @param string $name
   *   The name of the config object.
   * @param array $original_data
   *   The original data stored in the config object.
   * @param array $custom_data
   *   The new data to store in the config object.
   *
   * @internal
   */
  public function assertConfigUpdateImport(string $name, array $original_data, array $custom_data): void {
    $this->container->get('config.storage.sync')->write($name, $custom_data);

    // Verify the active configuration still returns the default values.
    $config = $this->config($name);
    $this->assertSame($config->get(), $original_data);

    // Import.
    $this->configImporter()->import();

    // Verify the values were updated.
    $this->container->get('config.factory')->reset($name);
    $config = $this->config($name);
    $this->assertSame($config->get(), $custom_data);
  }

}
