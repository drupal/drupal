<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageTransformerException;
use Drupal\Core\Lock\NullLockBackend;
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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

  /**
   * Test that the import transformer throws an exception.
   */
  public function testTransformLocked() {
    // Mock the request lock not being available.
    $lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $lock->expects($this->at(0))
      ->method('acquire')
      ->with(ImportStorageTransformer::LOCK_NAME)
      ->will($this->returnValue(FALSE));
    $lock->expects($this->at(1))
      ->method('wait')
      ->with(ImportStorageTransformer::LOCK_NAME);
    $lock->expects($this->at(2))
      ->method('acquire')
      ->with(ImportStorageTransformer::LOCK_NAME)
      ->will($this->returnValue(FALSE));

    // The import transformer under test.
    $transformer = new ImportStorageTransformer(
      $this->container->get('event_dispatcher'),
      $this->container->get('database'),
      $lock,
      new NullLockBackend()
    );

    $this->expectException(StorageTransformerException::class);
    $this->expectExceptionMessage("Cannot acquire config import transformer lock.");
    $transformer->transform(new MemoryStorage());
  }

  /**
   * Test the import transformer during a running config import.
   */
  public function testTransformWhileImporting() {
    // Set up the database table with the current active config.
    // This simulates the config importer having its transformation done.
    $storage = $this->container->get('config.import_transformer')->transform($this->container->get('config.storage'));

    // Mock the persistent lock being unavailable due to a config import.
    $lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $lock->expects($this->at(0))
      ->method('lockMayBeAvailable')
      ->with(ConfigImporter::LOCK_NAME)
      ->will($this->returnValue(FALSE));

    // The import transformer under test.
    $transformer = new ImportStorageTransformer(
      $this->container->get('event_dispatcher'),
      $this->container->get('database'),
      new NullLockBackend(),
      $lock
    );

    // Transform an empty memory storage.
    $import = $transformer->transform(new MemoryStorage());
    // Assert that the transformed storage is the same as the one used to
    // set up the simulated config importer.
    $this->assertEquals($storage->listAll(), $import->listAll());
    $this->assertNotEmpty($import->read('system.site'));
  }

}
