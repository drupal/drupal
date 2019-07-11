<?php

// @codingStandardsIgnoreStart
// @todo: Move this back to \Drupal\KernelTests\Core\Config in #2991683.
// @codingStandardsIgnoreEnd
namespace Drupal\Tests\config_environment\Kernel\Core\Config;

use Drupal\Core\Config\MemoryStorage;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the import storage transformer.
 *
 * @group config
 */
class ImportStorageTransformerTest extends KernelTestBase {

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
   * Test the import transformation.
   */
  public function testTransform() {
    // Get the raw system.site config and set it in the sync storage.
    $rawConfig = $this->config('system.site')->getRawData();

    $storage = new MemoryStorage();
    $this->copyConfig($this->container->get('config.storage'), $storage);

    $import = $this->container->get('config.import_transformer')->transform($storage);
    $config = $import->read('system.site');
    // The test subscriber always adds "Arrr" to the current site name.
    $this->assertEquals($rawConfig['name'] . ' Arrr', $config['name']);
    $this->assertEquals($rawConfig['slogan'], $config['slogan']);

    // Update the site config in the storage to test a second transformation.
    $config['name'] = 'New name';
    $config['slogan'] = 'New slogan';
    $storage->write('system.site', $config);

    $import = $this->container->get('config.import_transformer')->transform($storage);
    $config = $import->read('system.site');
    // The test subscriber always adds "Arrr" to the current site name.
    $this->assertEquals($rawConfig['name'] . ' Arrr', $config['name']);
    $this->assertEquals('New slogan', $config['slogan']);
  }

}
