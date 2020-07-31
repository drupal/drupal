<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configuration export storage.
 *
 * @group config
 */
class ConfigExportStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'config_test'];

  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'config_test']);
  }

  /**
   * Tests configuration override.
   */
  public function testExportStorage() {
    /** @var \Drupal\Core\Config\StorageInterface $active */
    $active = $this->container->get('config.storage');
    /** @var \Drupal\Core\Config\StorageInterface $export */
    $export = $this->container->get('config.storage.export');

    // Test that the active and the export storage contain the same config.
    $this->assertNotEmpty($active->listAll());
    $this->assertEquals($active->listAll(), $export->listAll());
    foreach ($active->listAll() as $name) {
      $this->assertEquals($active->read($name), $export->read($name));
    }

    // Test that the export storage is read-only.
    $this->expectException(\BadMethodCallException::class);
    $export->deleteAll();
  }

}
