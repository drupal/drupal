<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigEntityStorageControllerTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Config\ConfigDuplicateUUIDException;

/**
 * Tests importing config entity data when the ID or UUID matches existing data.
 */
class ConfigEntityStorageControllerTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration entity UUID conflict',
      'description' => 'Tests staging and importing config entities with IDs and UUIDs that match existing config.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests importing fields and instances with changed IDs or UUIDs.
   */
  public function testUUIDConflict() {
    $entity_type = 'config_test';
    $id = 'test_1';
    // Load the original field and instance entities.
    entity_create($entity_type, array('id' => $id))->save();
    $entity = entity_load($entity_type, $id);

    $original_properties = $entity->getExportProperties();

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
    $this->assertIdentical($entity->getExportProperties(), $original_properties);
  }

}
