<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configuration export storage.
 *
 * @group config
 */
class ConfigExportStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'config_test']);
  }

  /**
   * Tests configuration override.
   */
  public function testExportStorage(): void {
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
