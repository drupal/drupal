<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityUUIDTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Component\Uuid\Uuid;
use Drupal\simpletest\WebTestBase;

/**
 * Tests creation, saving, and loading of entity UUIDs.
 */
class EntityUUIDTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity UUIDs',
      'description' => 'Tests creation, saving, and loading of entity UUIDs.',
      'group' => 'Entity API',
    );
  }

  /**
   * Tests UUID generation in entity CRUD operations.
   */
  function testCRUD() {
    // Verify that no UUID is auto-generated when passing one for creation.
    $uuid_service = new Uuid();
    $uuid = $uuid_service->generate();
    $custom_entity = entity_create('entity_test', array(
      'name' => $this->randomName(),
      'uuid' => $uuid,
    ));
    $this->assertIdentical($custom_entity->uuid(), $uuid);
    // Save this entity, so we have more than one later.
    $custom_entity->save();

    // Verify that a new UUID is generated upon creating an entity.
    $entity = entity_create('entity_test', array('name' => $this->randomName()));
    $uuid = $entity->uuid();
    $this->assertTrue($uuid);

    // Verify that the new UUID is different.
    $this->assertNotEqual($custom_entity->uuid(), $uuid);

    // Verify that the UUID is retained upon saving.
    $entity->save();
    $this->assertIdentical($entity->uuid(), $uuid);

    // Verify that the UUID is retained upon loading.
    $entity_loaded = entity_test_load($entity->id(), TRUE);
    $this->assertIdentical($entity_loaded->uuid(), $uuid);

    // Verify that entity_load_by_uuid() loads the same entity.
    $entity_loaded_by_uuid = entity_load_by_uuid('entity_test', $uuid, TRUE);
    $this->assertIdentical($entity_loaded_by_uuid->uuid(), $uuid);
    $this->assertEqual($entity_loaded_by_uuid->id(), $entity_loaded->id());

    // Creating a duplicate needs to result in a new UUID.
    $entity_duplicate = $entity->createDuplicate();
    foreach ($entity->getProperties() as $property => $value) {
      switch($property) {
        case 'uuid':
          $this->assertNotNull($entity_duplicate->uuid());
          $this->assertNotNull($entity->uuid());
          $this->assertNotEqual($entity_duplicate->uuid(), $entity->uuid());
          break;
        case 'id':
          $this->assertNull($entity_duplicate->id());
          $this->assertNotNull($entity->id());
          $this->assertNotEqual($entity_duplicate->id(), $entity->id());
          break;
        default:
          $this->assertEqual($entity_duplicate->{$property}->value, $entity->{$property}->value);
      }
    }
    $entity_duplicate->save();
    $this->assertNotEqual($entity->id(), $entity_duplicate->id());
  }
}
