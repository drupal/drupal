<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigCollectionEvents;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests installation of configuration objects in installation functionality.
 *
 * @group config
 * @see \Drupal\Core\Config\ConfigInstaller
 */
class ConfigInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'config_events_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure the global variable being asserted by this test does not exist;
    // a previous test executed in this request/process might have set it.
    unset($GLOBALS['hook_config_test']);
  }

  /**
   * Tests module installation.
   */
  public function testModuleInstallation(): void {
    $default_config = 'config_test.system';
    $default_configuration_entity = 'config_test.dynamic.dotted.default';

    // Verify that default module config does not exist before installation yet.
    $config = $this->config($default_config);
    $this->assertTrue($config->isNew());
    $config = $this->config($default_configuration_entity);
    $this->assertTrue($config->isNew());

    // Ensure that schema provided by modules that are not installed is not
    // available.
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.some_schema'), 'Configuration schema for config_schema_test.some_schema does not exist.');

    // Install the test module.
    $this->installModules(['config_test']);

    // Verify that default module config exists.
    \Drupal::configFactory()->reset($default_config);
    \Drupal::configFactory()->reset($default_configuration_entity);
    $config = $this->config($default_config);
    $this->assertFalse($config->isNew());
    $config = $this->config($default_configuration_entity);
    $this->assertFalse($config->isNew());

    // Verify that config_test API hooks were invoked for the dynamic default
    // configuration entity.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Install the schema test module.
    $this->enableModules(['config_schema_test']);
    $this->installConfig(['config_schema_test']);

    // After module installation the new schema should exist.
    $this->assertTrue(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.some_schema'), 'Configuration schema for config_schema_test.some_schema exists.');

    // Test that uninstalling configuration removes configuration schema.
    $this->config('core.extension')->set('module', [])->save();
    \Drupal::service('config.manager')->uninstall('module', 'config_test');
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.some_schema'), 'Configuration schema for config_schema_test.some_schema does not exist.');
  }

  /**
   * Tests that collections are ignored if the event does not return anything.
   */
  public function testCollectionInstallationNoCollections(): void {
    // Install the test module.
    $this->enableModules(['config_collection_install_test']);
    $this->installConfig(['config_collection_install_test']);
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEquals([], $active_storage->getAllCollectionNames());
  }

  /**
   * Tests config objects in collections are installed as expected.
   */
  public function testCollectionInstallationCollections(): void {
    $collections = [
      'another_collection',
      'collection.test1',
      'collection.test2',
    ];
    // Set the event listener to return three possible collections.
    // @see \Drupal\config_collection_install_test\EventSubscriber
    \Drupal::state()->set('config_collection_install_test.collection_names', $collections);
    // Install the test module.
    $this->enableModules(['config_collection_install_test']);
    $this->installConfig(['config_collection_install_test']);
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEquals($collections, $active_storage->getAllCollectionNames());
    foreach ($collections as $collection) {
      $collection_storage = $active_storage->createCollection($collection);
      $data = $collection_storage->read('config_collection_install_test.test');
      $this->assertEquals($collection, $data['collection']);
    }

    // Tests that clashing configuration in collections is detected.
    try {
      \Drupal::service('module_installer')->install(['config_collection_clash_install_test']);
      $this->fail('Expected PreExistingConfigException not thrown.');
    }
    catch (PreExistingConfigException $e) {
      $this->assertEquals('config_collection_clash_install_test', $e->getExtension());
      $this->assertEquals(['another_collection' => ['config_collection_install_test.test'], 'collection.test1' => ['config_collection_install_test.test'], 'collection.test2' => ['config_collection_install_test.test']], $e->getConfigObjects());
      $this->assertEquals('Configuration objects (another_collection/config_collection_install_test.test, collection/test1/config_collection_install_test.test, collection/test2/config_collection_install_test.test) provided by config_collection_clash_install_test already exist in active configuration', $e->getMessage());
    }

    // Test that the we can use the config installer to install all the
    // available default configuration in a particular collection for enabled
    // extensions.
    \Drupal::service('config.installer')->installCollectionDefaultConfig('entity');
    // The 'entity' collection will not exist because the 'config_test' module
    // is not enabled.
    $this->assertEquals($collections, $active_storage->getAllCollectionNames());
    // Enable the 'config_test' module and try again.
    $this->enableModules(['config_test']);
    \Drupal::service('config.installer')->installCollectionDefaultConfig('entity');
    $collections[] = 'entity';
    $this->assertEquals($collections, $active_storage->getAllCollectionNames());
    $collection_storage = $active_storage->createCollection('entity');
    $data = $collection_storage->read('config_test.dynamic.dotted.default');
    $this->assertSame(['label' => 'entity'], $data);

    // Test that the config manager uninstalls configuration from collections
    // as expected.
    \Drupal::state()->set('config_events_test.all_events', []);
    $this->container->get('config.manager')->uninstall('module', 'config_collection_install_test');
    $all_events = \Drupal::state()->get('config_events_test.all_events');
    $this->assertArrayHasKey(ConfigCollectionEvents::DELETE_IN_COLLECTION, $all_events);
    // The delete-in-collection event has been triggered 3 times.
    $this->assertCount(3, $all_events[ConfigCollectionEvents::DELETE_IN_COLLECTION]['config_collection_install_test.test']);
    $event_collections = [];
    foreach ($all_events[ConfigCollectionEvents::DELETE_IN_COLLECTION]['config_collection_install_test.test'] as $event) {
      $event_collections[] = $event['original_config_data']['collection'];
    }
    $this->assertSame(['another_collection', 'collection.test1', 'collection.test2'], $event_collections);
    $this->assertEquals(['entity'], $active_storage->getAllCollectionNames());

    \Drupal::state()->set('config_events_test.all_events', []);
    $this->container->get('config.manager')->uninstall('module', 'config_test');
    $this->assertEquals([], $active_storage->getAllCollectionNames());
    $all_events = \Drupal::state()->get('config_events_test.all_events');
    $this->assertArrayHasKey(ConfigCollectionEvents::DELETE_IN_COLLECTION, $all_events);
    $this->assertCount(1, $all_events[ConfigCollectionEvents::DELETE_IN_COLLECTION]['config_test.dynamic.dotted.default']);
  }

  /**
   * Tests collections which do not support config entities install correctly.
   *
   * Config entity detection during config installation is done by matching
   * config name prefixes. If a collection provides a configuration with a
   * matching name but does not support config entities it should be created
   * using simple configuration.
   */
  public function testCollectionInstallationCollectionConfigEntity(): void {
    $collections = [
      'entity',
    ];
    \Drupal::state()->set('config_collection_install_test.collection_names', $collections);
    // Install the test module.
    $this->installModules(['config_test', 'config_collection_install_test']);
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEquals($collections, $active_storage->getAllCollectionNames());
    $collection_storage = $active_storage->createCollection('entity');

    // The config_test.dynamic.dotted.default configuration object saved in the
    // active store should be a configuration entity complete with UUID. Because
    // the entity collection does not support configuration entities the
    // configuration object stored there with the same name should only contain
    // a label.
    $name = 'config_test.dynamic.dotted.default';
    $data = $active_storage->read($name);
    $this->assertTrue(isset($data['uuid']));
    $data = $collection_storage->read($name);
    $this->assertSame(['label' => 'entity'], $data);
  }

  /**
   * Tests the configuration with unmet dependencies is not installed.
   */
  public function testDependencyChecking(): void {
    $this->installModules(['config_test']);
    try {
      $this->installModules(['config_install_dependency_test']);
      $this->fail('Expected UnmetDependenciesException not thrown.');
    }
    catch (UnmetDependenciesException $e) {
      $this->assertEquals('config_install_dependency_test', $e->getExtension());
      $this->assertEquals(['config_test.dynamic.other_module_test_with_dependency' => ['config_other_module_config_test', 'config_test.dynamic.dotted.english']], $e->getConfigObjects());
      $this->assertEquals('Configuration objects provided by <em class="placeholder">config_install_dependency_test</em> have unmet dependencies: <em class="placeholder">config_test.dynamic.other_module_test_with_dependency (config_other_module_config_test, config_test.dynamic.dotted.english)</em>', $e->getMessage());
    }
    try {
      $this->installModules(['config_install_double_dependency_test']);
      $this->fail('Expected UnmetDependenciesException not thrown.');
    }
    catch (UnmetDependenciesException $e) {
      $this->assertEquals('config_install_double_dependency_test', $e->getExtension());
      $this->assertEquals(['config_test.dynamic.other_module_test_with_dependency' => ['config_other_module_config_test', 'config_test.dynamic.dotted.english']], $e->getConfigObjects());
      $this->assertEquals('Configuration objects provided by <em class="placeholder">config_install_double_dependency_test</em> have unmet dependencies: <em class="placeholder">config_test.dynamic.other_module_test_with_dependency (config_other_module_config_test, config_test.dynamic.dotted.english)</em>', $e->getMessage());
    }
    $this->installModules(['config_test_language']);
    try {
      $this->installModules(['config_install_dependency_test']);
      $this->fail('Expected UnmetDependenciesException not thrown.');
    }
    catch (UnmetDependenciesException $e) {
      $this->assertEquals('config_install_dependency_test', $e->getExtension());
      $this->assertEquals(['config_test.dynamic.other_module_test_with_dependency' => ['config_other_module_config_test']], $e->getConfigObjects());
      $this->assertEquals('Configuration objects provided by <em class="placeholder">config_install_dependency_test</em> have unmet dependencies: <em class="placeholder">config_test.dynamic.other_module_test_with_dependency (config_other_module_config_test)</em>', $e->getMessage());
    }
    $this->installModules(['config_other_module_config_test']);
    $this->installModules(['config_install_dependency_test']);
    $entity = \Drupal::entityTypeManager()->getStorage('config_test')->load('other_module_test_with_dependency');
    $this->assertNotEmpty($entity, 'The config_test.dynamic.other_module_test_with_dependency configuration has been created during install.');
    // Ensure that dependencies can be added during module installation by
    // hooks.
    $this->assertSame('config_install_dependency_test', $entity->getDependencies()['module'][0]);
  }

  /**
   * Tests imported configuration entities with/without language information.
   */
  public function testLanguage(): void {
    $this->installModules(['config_test_language']);
    // Test imported configuration with implicit language code.
    $storage = new InstallStorage();
    $data = $storage->read('config_test.dynamic.dotted.english');
    $this->assertTrue(!isset($data['langcode']));
    $this->assertEquals('en', $this->config('config_test.dynamic.dotted.english')->get('langcode'));

    // Test imported configuration with explicit language code.
    $data = $storage->read('config_test.dynamic.dotted.french');
    $this->assertEquals('fr', $data['langcode']);
    $this->assertEquals('fr', $this->config('config_test.dynamic.dotted.french')->get('langcode'));
  }

  /**
   * Tests installing configuration where the filename and ID do not match.
   */
  public function testIdMisMatch(): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The configuration name "config_test.dynamic.no_id_match" does not match the ID "does_not_match"');
    $this->installModules(['config_test_id_mismatch']);
  }

  /**
   * Installs a module.
   *
   * @param array $modules
   *   The module names.
   */
  protected function installModules(array $modules) {
    $this->container->get('module_installer')->install($modules);
    $this->container = \Drupal::getContainer();
  }

}
