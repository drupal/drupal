<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigDuplicateUUIDException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests sync and importing config entities with IDs and UUIDs that match
 * existing config.
 *
 * @group config
 */
class ConfigEntityStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  /**
   * Tests creating configuration entities with changed UUIDs.
   */
  public function testUUIDConflict() {
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
    catch (ConfigDuplicateUUIDException $e) {
      $this->pass(format_string('Exception thrown when attempting to save a configuration entity with a UUID that does not match existing data: %e.', array('%e' => $e)));
    }

    // Ensure that the config entity was not corrupted.
    $entity = entity_load('config_test', $entity->id(), TRUE);
    $this->assertIdentical($entity->toArray(), $original_properties);
  }

}
