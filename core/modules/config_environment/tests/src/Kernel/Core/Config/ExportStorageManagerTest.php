<?php

// @codingStandardsIgnoreStart
// @todo: Move this back to \Drupal\KernelTests\Core\Config in #2991683.
// @codingStandardsIgnoreEnd
namespace Drupal\Tests\config_environment\Kernel\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the export storage manager.
 *
 * @group config
 */
class ExportStorageManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'config_transformer_test',
    'config_environment',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Test getting the export storage.
   */
  public function testGetStorage() {
    // Get the raw system.site config and set it in the sync storage.
    $rawConfig = $this->config('system.site')->getRawData();
    $this->container->get('config.storage.sync')->write('system.site', $rawConfig);

    $storage = $this->container->get('config.storage.export.manager')->getStorage();
    $exported = $storage->read('system.site');
    // The test subscriber adds "Arrr" to the slogan of the sync config.
    $this->assertEquals($rawConfig['name'], $exported['name']);
    $this->assertEquals($rawConfig['slogan'] . ' Arrr', $exported['slogan']);

    // Save the config to active storage so that the transformer can alter it.
    $this->config('system.site')
      ->set('name', 'New name')
      ->set('slogan', 'New slogan')
      ->save();

    // Get the storage again.
    $storage = $this->container->get('config.storage.export.manager')->getStorage();
    $exported = $storage->read('system.site');
    // The test subscriber adds "Arrr" to the slogan of the sync config.
    $this->assertEquals('New name', $exported['name']);
    $this->assertEquals($rawConfig['slogan'] . ' Arrr', $exported['slogan']);

    // Change what the transformer does without changing anything else to assert
    // that the event is dispatched every time the storage is needed.
    $this->container->get('state')->set('config_transform_test_mail', 'config@drupal.example');
    $storage = $this->container->get('config.storage.export.manager')->getStorage();
    $exported = $storage->read('system.site');
    // The mail is still set to the value from the beginning.
    $this->assertEquals('config@drupal.example', $exported['mail']);
  }

}
