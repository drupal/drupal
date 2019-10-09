<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests ExcludedModulesEventSubscriber.
 *
 * @group config
 */
class ExcludedModulesEventSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'config_test',
    'config_exclude_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'config_test', 'config_exclude_test']);
    $this->setSetting('config_exclude_modules', ['config_test']);
  }

  /**
   * Test excluding modules from the config export.
   */
  public function testExcludedModules() {
    // Assert that config_test is in the active config.
    $active = $this->container->get('config.storage');
    $this->assertNotEmpty($active->listAll('config_test.'));
    $this->assertNotEmpty($active->listAll('system.'));
    $this->assertArrayHasKey('config_test', $active->read('core.extension')['module']);
    $collection = $this->randomMachineName();
    foreach ($active->listAll() as $config) {
      $active->createCollection($collection)->write($config, $active->read($config));
    }

    // Assert that config_test is not in the export storage.
    $export = $this->container->get('config.storage.export');
    $this->assertEmpty($export->listAll('config_test.'));
    $this->assertNotEmpty($export->listAll('system.'));
    $this->assertEmpty($export->createCollection($collection)->listAll('config_test.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('system.'));
    $this->assertArrayNotHasKey('config_test', $export->read('core.extension')['module']);
    // The config_exclude_test is not excluded but the menu it installs are.
    $this->assertArrayHasKey('config_exclude_test', $export->read('core.extension')['module']);
    $this->assertFalse($export->exists('system.menu.exclude_test'));
    $this->assertFalse($export->exists('system.menu.indirect_exclude_test'));

    // Assert that config_test is again in the import storage.
    $import = $this->container->get('config.import_transformer')->transform($export);
    $this->assertNotEmpty($import->listAll('config_test.'));
    $this->assertNotEmpty($import->listAll('system.'));
    $this->assertNotEmpty($import->createCollection($collection)->listAll('config_test.'));
    $this->assertNotEmpty($import->createCollection($collection)->listAll('system.'));
    $this->assertArrayHasKey('config_test', $import->read('core.extension')['module']);
    $this->assertArrayHasKey('config_exclude_test', $import->read('core.extension')['module']);
    $this->assertTrue($import->exists('system.menu.exclude_test'));
    $this->assertTrue($import->exists('system.menu.indirect_exclude_test'));

    $this->assertEquals($active->read('core.extension'), $import->read('core.extension'));
    $this->assertEquals($active->listAll(), $import->listAll());
    foreach ($active->listAll() as $config) {
      $this->assertEquals($active->read($config), $import->read($config));
    }

    // When the settings are changed, the next request will get the export
    // storage without the config_test excluded.
    $this->setSetting('config_exclude_modules', []);
    // We rebuild the container to simulate a new request. The managed storage
    // gets the storage from the manager only once.
    $this->container->get('kernel')->rebuildContainer();
    $export = $this->container->get('config.storage.export');

    $this->assertArrayHasKey('config_test', $export->read('core.extension')['module']);
  }

}
