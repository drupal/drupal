<?php

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search\Entity\SearchPage;
use Drupal\system\Entity\Action;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests ConfigEntity importing.
 *
 * @group Entity
 */
class ConfigEntityImportTest extends KernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'action',
    'block',
    'config_test',
    'filter',
    'image',
    'search',
    'search_extra_type',
    'system',
  ];

  /**
   * Runs test methods for each module within a single test run.
   */
  public function testConfigUpdateImport() {
    $this->installConfig(['action', 'block', 'filter', 'image']);
    $this->container->get('theme_installer')->install(['bartik']);
    $config_storage = $this->container->get('config.storage');
    // Ensure the 'system.site' config.
    $config_storage->write('system.site', ['uuid' => (new Php())->generate()]);
    $this->copyConfig($config_storage, $this->container->get('config.storage.sync'));

    $this->doActionUpdate();
    $this->doBlockUpdate();
    $this->doFilterFormatUpdate();
    $this->doImageStyleUpdate();
    $this->doSearchPageUpdate();
    $this->doThirdPartySettingsUpdate();
  }

  /**
   * Tests updating a action during import.
   */
  protected function doActionUpdate() {
    // Create a test action with a known label.
    $name = 'system.action.apple';
    $entity = Action::create([
      'id' => 'apple',
      'plugin' => 'action_message_action',
    ]);
    $entity->save();

    $this->checkSinglePluginConfigSync($entity, 'configuration', 'message', '');

    // Read the existing data, and prepare an altered version in sync.
    $custom_data = $original_data = $this->container->get('config.storage')->read($name);
    $custom_data['configuration']['message'] = 'Granny Smith';
    $this->assertConfigUpdateImport($name, $original_data, $custom_data);

  }

  /**
   * Tests updating a block during import.
   */
  protected function doBlockUpdate() {
    // Create a test block with a known label.
    $name = 'block.block.apple';
    $block = $this->placeBlock('system_powered_by_block', [
      'id' => 'apple',
      'label' => 'Red Delicious',
      'theme' => 'bartik',
    ]);

    $this->checkSinglePluginConfigSync($block, 'settings', 'label', 'Red Delicious');

    // Read the existing data, and prepare an altered version in sync.
    $custom_data = $original_data = $this->container->get('config.storage')->read($name);
    $custom_data['settings']['label'] = 'Granny Smith';
    $this->assertConfigUpdateImport($name, $original_data, $custom_data);
  }

  /**
   * Tests updating a filter format during import.
   */
  protected function doFilterFormatUpdate() {
    // Create a test filter format with a known label.
    $name = 'filter.format.plain_text';

    /** @var $entity \Drupal\filter\Entity\FilterFormat */
    $entity = FilterFormat::load('plain_text');
    $plugin_collection = $entity->getPluginCollections()['filters'];

    $filters = $entity->get('filters');
    $this->assertSame(72, $filters['filter_url']['settings']['filter_url_length']);

    $filters['filter_url']['settings']['filter_url_length'] = 100;
    $entity->set('filters', $filters);
    $entity->save();
    $this->assertSame($filters, $entity->get('filters'));
    $this->assertSame($filters, $plugin_collection->getConfiguration());

    $filters['filter_url']['settings']['filter_url_length'] = -100;
    $entity->getPluginCollections()['filters']->setConfiguration($filters);
    $entity->save();
    $this->assertSame($filters, $entity->get('filters'));
    $this->assertSame($filters, $plugin_collection->getConfiguration());

    // Read the existing data, and prepare an altered version in sync.
    $custom_data = $original_data = $this->container->get('config.storage')->read($name);
    $custom_data['filters']['filter_url']['settings']['filter_url_length'] = 100;
    $this->assertConfigUpdateImport($name, $original_data, $custom_data);
  }

  /**
   * Tests updating an image style during import.
   */
  protected function doImageStyleUpdate() {
    // Create a test image style with a known label.
    $name = 'image.style.thumbnail';

    /** @var $entity \Drupal\image\Entity\ImageStyle */
    $entity = ImageStyle::load('thumbnail');
    $plugin_collection = $entity->getPluginCollections()['effects'];

    $effects = $entity->get('effects');
    $effect_id = key($effects);
    $this->assertSame(100, $effects[$effect_id]['data']['height']);

    $effects[$effect_id]['data']['height'] = 50;
    $entity->set('effects', $effects);
    $entity->save();
    // Ensure the entity and plugin have the correct configuration.
    $this->assertSame($effects, $entity->get('effects'));
    $this->assertSame($effects, $plugin_collection->getConfiguration());

    $effects[$effect_id]['data']['height'] = -50;
    $entity->getPluginCollections()['effects']->setConfiguration($effects);
    $entity->save();
    // Ensure the entity and plugin have the correct configuration.
    $this->assertSame($effects, $entity->get('effects'));
    $this->assertSame($effects, $plugin_collection->getConfiguration());

    // Read the existing data, and prepare an altered version in sync.
    $custom_data = $original_data = $this->container->get('config.storage')->read($name);
    $effect_name = key($original_data['effects']);

    $custom_data['effects'][$effect_name]['data']['upscale'] = FALSE;
    $this->assertConfigUpdateImport($name, $original_data, $custom_data);
  }

  /**
   * Tests updating a search page during import.
   */
  protected function doSearchPageUpdate() {
    // Create a test search page with a known label.
    $name = 'search.page.apple';
    $entity = SearchPage::create([
      'id' => 'apple',
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
   * Tests updating of third party settings.
   */
  protected function doThirdPartySettingsUpdate() {
    // Create a test action with a known label.
    $name = 'system.action.third_party_settings_test';

    /** @var \Drupal\config_test\Entity\ConfigTest $entity */
    $entity = Action::create([
      'id' => 'third_party_settings_test',
      'plugin' => 'action_message_action',
    ]);
    $entity->save();

    $this->assertSame([], $entity->getThirdPartyProviders());
    // Get a copy of the configuration before the third party setting is added.
    $no_third_part_setting_config = $this->container->get('config.storage')->read($name);

    // Add a third party setting.
    $entity->setThirdPartySetting('config_test', 'integer', 1);
    $entity->save();
    $this->assertSame(1, $entity->getThirdPartySetting('config_test', 'integer'));
    $has_third_part_setting_config = $this->container->get('config.storage')->read($name);

    // Ensure configuration imports can completely remove third party settings.
    $this->assertConfigUpdateImport($name, $has_third_part_setting_config, $no_third_part_setting_config);
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
  protected function checkSinglePluginConfigSync(EntityWithPluginCollectionInterface $entity, $config_key, $setting_key, $expected) {
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
   */
  public function assertConfigUpdateImport($name, $original_data, $custom_data) {
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
