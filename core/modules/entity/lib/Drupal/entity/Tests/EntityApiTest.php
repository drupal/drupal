<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\EntityApiTest.
 */

namespace Drupal\entity\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the basic Entity API.
 */
class EntityApiTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Entity CRUD',
      'description' => 'Tests basic CRUD functionality.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp('entity', 'entity_test');
  }

  /**
   * Tests basic CRUD functionality of the Entity API.
   */
  function testCRUD() {
    $user1 = $this->drupalCreateUser();

    // Create some test entities.
    $entity = entity_create('entity_test', array('name' => 'test', 'uid' => $user1->uid));
    $entity->save();
    $entity = entity_create('entity_test', array('name' => 'test2', 'uid' => $user1->uid));
    $entity->save();
    $entity = entity_create('entity_test', array('name' => 'test', 'uid' => NULL));
    $entity->save();

    $entities = array_values(entity_test_load_multiple(FALSE, array('name' => 'test')));

    $this->assertEqual($entities[0]->name, 'test', 'Created and loaded entity.');
    $this->assertEqual($entities[1]->name, 'test', 'Created and loaded entity.');

    // Test loading a single entity.
    $loaded_entity = entity_test_load($entity->id);
    $this->assertEqual($loaded_entity->id, $entity->id, 'Loaded a single entity by id.');

    // Test deleting an entity.
    $entities = array_values(entity_test_load_multiple(FALSE, array('name' => 'test2')));
    $entities[0]->delete();
    $entities = array_values(entity_test_load_multiple(FALSE, array('name' => 'test2')));
    $this->assertEqual($entities, array(), 'Entity deleted.');

    // Test updating an entity.
    $entities = array_values(entity_test_load_multiple(FALSE, array('name' => 'test')));
    $entities[0]->name = 'test3';
    $entities[0]->save();
    $entity = entity_test_load($entities[0]->id);
    $this->assertEqual($entity->name, 'test3', 'Entity updated.');

    // Try deleting multiple test entities by deleting all.
    $ids = array_keys(entity_test_load_multiple(FALSE));
    entity_test_delete_multiple($ids);

    $all = entity_test_load_multiple(FALSE);
    $this->assertTrue(empty($all), 'Deleted all entities.');
  }

  /**
   * Tests Entity getters/setters.
   */
  function testEntityGettersSetters() {
    $entity = entity_create('entity_test', array('name' => 'test', 'uid' => NULL));
    $this->assertNull($entity->get('uid'), 'Property is not set.');

    $entity->set('uid', $GLOBALS['user']->uid);
    $this->assertEqual($entity->uid, $GLOBALS['user']->uid, 'Property has been set.');

    $value = $entity->get('uid');
    $this->assertEqual($value, $entity->uid, 'Property has been retrieved.');

    // Make sure setting/getting translations boils down to setting/getting the
    // regular value as the entity and property are not translatable.
    $entity->set('uid', NULL, 'en');
    $this->assertNull($entity->uid, 'Language neutral property has been set.');

    $value = $entity->get('uid', 'en');
    $this->assertNull($value, 'Language neutral property has been retrieved.');
  }
}
