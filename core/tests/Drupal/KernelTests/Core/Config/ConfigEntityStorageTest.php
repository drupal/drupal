<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigDuplicateUUIDException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configuration entity storage.
 *
 * @group config
 */
class ConfigEntityStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * Tests creating configuration entities with changed UUIDs.
   */
  public function testUUIDConflict(): void {
    $entity_type = 'config_test';
    $id = 'test_1';
    // Load the original configuration entity.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);
    $storage->create(['id' => $id])->save();
    $entity = $storage->load($id);

    $original_properties = $entity->toArray();

    // Override with a new UUID and try to save.
    $new_uuid = $this->container->get('uuid')->generate();
    $entity->set('uuid', $new_uuid);

    try {
      $entity->save();
      $this->fail('Exception thrown when attempting to save a configuration entity with a UUID that does not match the existing UUID.');
    }
    catch (ConfigDuplicateUUIDException) {
      // Expected exception; just continue testing.
    }

    // Ensure that the config entity was not corrupted.
    $entity = $storage->loadUnchanged($entity->id());
    $this->assertSame($original_properties, $entity->toArray());
  }

  /**
   * Tests the hasData() method for config entity storage.
   *
   * @covers \Drupal\Core\Config\Entity\ConfigEntityStorage::hasData
   */
  public function testHasData(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');
    $this->assertFalse($storage->hasData());

    // Add a test config entity and check again.
    $storage->create(['id' => $this->randomMachineName()])->save();
    $this->assertTrue($storage->hasData());
  }

}
