<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ExportStorageManager;
use Drupal\Core\Config\StorageTransformerException;
use Drupal\Core\Lock\NullLockBackend;
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests getting the export storage.
   */
  public function testGetStorage() {
    // Get the raw system.site config and set it in the sync storage.
    $rawConfig = $this->config('system.site')->getRawData();
    $this->container->get('config.storage.sync')->write('system.site', $rawConfig);

    // The export storage manager under test.
    $manager = new ExportStorageManager(
      $this->container->get('config.storage'),
      $this->container->get('database'),
      $this->container->get('event_dispatcher'),
      new NullLockBackend()
    );

    $storage = $manager->getStorage();
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
    $storage = $manager->getStorage();
    $exported = $storage->read('system.site');
    // The test subscriber adds "Arrr" to the slogan of the sync config.
    $this->assertEquals('New name', $exported['name']);
    $this->assertEquals($rawConfig['slogan'] . ' Arrr', $exported['slogan']);

    // Change what the transformer does without changing anything else to assert
    // that the event is dispatched every time the storage is needed.
    $this->container->get('state')->set('config_transform_test_mail', 'config@drupal.example');
    $storage = $manager->getStorage();
    $exported = $storage->read('system.site');
    // The mail is still set to the value from the beginning.
    $this->assertEquals('config@drupal.example', $exported['mail']);
  }

  /**
   * Tests the export storage when it is locked.
   */
  public function testGetStorageLock() {
    $lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $lock->expects($this->exactly(2))
      ->method('acquire')
      ->with(ExportStorageManager::LOCK_NAME)
      ->will($this->returnValue(FALSE));
    $lock->expects($this->once())
      ->method('wait')
      ->with(ExportStorageManager::LOCK_NAME);

    // The export storage manager under test.
    $manager = new ExportStorageManager(
      $this->container->get('config.storage'),
      $this->container->get('database'),
      $this->container->get('event_dispatcher'),
      $lock
    );

    $this->expectException(StorageTransformerException::class);
    $this->expectExceptionMessage("Cannot acquire config export transformer lock.");
    $manager->getStorage();
  }

}
