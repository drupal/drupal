<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityApiTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the basic Entity API.
 */
class EntityApiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity CRUD',
      'description' => 'Tests basic CRUD functionality.',
      'group' => 'Entity API',
    );
  }

  /**
   * Tests basic CRUD functionality of the Entity API.
   */
  function testCRUD() {
    $user1 = $this->drupalCreateUser();

    // Create some test entities.
    $entity = entity_create('entity_test', array('name' => 'test', 'user_id' => $user1->uid));
    $entity->save();
    $entity = entity_create('entity_test', array('name' => 'test2', 'user_id' => $user1->uid));
    $entity->save();
    $entity = entity_create('entity_test', array('name' => 'test', 'user_id' => NULL));
    $entity->save();

    $entities = array_values(entity_load_multiple_by_properties('entity_test', array('name' => 'test')));
    $this->assertEqual($entities[0]->name->value, 'test', 'Created and loaded entity.');
    $this->assertEqual($entities[1]->name->value, 'test', 'Created and loaded entity.');

    // Test loading a single entity.
    $loaded_entity = entity_test_load($entity->id());
    $this->assertEqual($loaded_entity->id(), $entity->id(), 'Loaded a single entity by id.');

    // Test deleting an entity.
    $entities = array_values(entity_load_multiple_by_properties('entity_test', array('name' => 'test2')));
    $entities[0]->delete();
    $entities = array_values(entity_load_multiple_by_properties('entity_test', array('name' => 'test2')));
    $this->assertEqual($entities, array(), 'Entity deleted.');

    // Test updating an entity.
    $entities = array_values(entity_load_multiple_by_properties('entity_test', array('name' => 'test')));
    $entities[0]->name->value = 'test3';
    $entities[0]->save();
    $entity = entity_test_load($entities[0]->id());
    $this->assertEqual($entity->name->value, 'test3', 'Entity updated.');

    // Try deleting multiple test entities by deleting all.
    $ids = array_keys(entity_test_load_multiple());
    entity_test_delete_multiple($ids);

    $all = entity_test_load_multiple();
    $this->assertTrue(empty($all), 'Deleted all entities.');
  }
}
