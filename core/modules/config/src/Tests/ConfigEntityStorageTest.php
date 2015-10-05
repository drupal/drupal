<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigEntityStorageTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\Core\Config\ConfigDuplicateUUIDException;

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
    entity_create($entity_type, array('id' => $id))->save();
    $entity = entity_load($entity_type, $id);

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
