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
  public function testCRUD() {
    $user1 = $this->drupalCreateUser();

    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->assertCRUD($entity_type, $user1);
    }
  }

  /**
   * Executes a test set for a defined entity type and user.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   * @param \Drupal\user\Plugin\Core\Entity\User $user1
   *   The user to run the tests with.
   */
  protected function assertCRUD($entity_type, \Drupal\user\Plugin\Core\Entity\User $user1) {
    // Create some test entities.
    $entity = entity_create($entity_type, array('name' => 'test', 'user_id' => $user1->uid));
    $entity->save();
    $entity = entity_create($entity_type, array('name' => 'test2', 'user_id' => $user1->uid));
    $entity->save();
    $entity = entity_create($entity_type, array('name' => 'test', 'user_id' => NULL));
    $entity->save();

    $entities = array_values(entity_load_multiple_by_properties($entity_type, array('name' => 'test')));
    $this->assertEqual($entities[0]->name->value, 'test', format_string('%entity_type: Created and loaded entity', array('%entity_type' => $entity_type)));
    $this->assertEqual($entities[1]->name->value, 'test', format_string('%entity_type: Created and loaded entity', array('%entity_type' => $entity_type)));

    // Test loading a single entity.
    $loaded_entity = entity_load($entity_type, $entity->id());
    $this->assertEqual($loaded_entity->id(), $entity->id(), format_string('%entity_type: Loaded a single entity by id.', array('%entity_type' => $entity_type)));

    // Test deleting an entity.
    $entities = array_values(entity_load_multiple_by_properties($entity_type, array('name' => 'test2')));
    $entities[0]->delete();
    $entities = array_values(entity_load_multiple_by_properties($entity_type, array('name' => 'test2')));
    $this->assertEqual($entities, array(), format_string('%entity_type: Entity deleted.', array('%entity_type' => $entity_type)));

    // Test updating an entity.
    $entities = array_values(entity_load_multiple_by_properties($entity_type, array('name' => 'test')));
    $entities[0]->name->value = 'test3';
    $entities[0]->save();
    $entity = entity_load($entity_type, $entities[0]->id());
    $this->assertEqual($entity->name->value, 'test3', format_string('%entity_type: Entity updated.', array('%entity_type' => $entity_type)));

    // Try deleting multiple test entities by deleting all.
    $ids = array_keys(entity_load_multiple($entity_type));
    entity_delete_multiple($entity_type, $ids);

    $all = entity_load_multiple($entity_type);
    $this->assertTrue(empty($all), format_string('%entity_type: Deleted all entities.', array('%entity_type' => $entity_type)));
  }
}
