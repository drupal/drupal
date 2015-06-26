<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityApiTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\UserInterface;

/**
 * Tests basic CRUD functionality.
 *
 * @group Entity
 */
class EntityApiTest extends EntityUnitTestBase {

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();

    foreach (entity_test_entity_types() as $entity_type_id) {
      // The entity_test schema is installed by the parent.
      if ($entity_type_id != 'entity_test') {
        $this->installEntitySchema($entity_type_id);
      }
    }
  }

  /**
   * Tests basic CRUD functionality of the Entity API.
   */
  public function testCRUD() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->assertCRUD($entity_type, $this->createUser());
    }
  }

  /**
   * Executes a test set for a defined entity type and user.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   * @param \Drupal\user\UserInterface $user1
   *   The user to run the tests with.
   */
  protected function assertCRUD($entity_type, UserInterface $user1) {
    // Create some test entities.
    $entity = entity_create($entity_type, array('name' => 'test', 'user_id' => $user1->id()));
    $entity->save();
    $entity = entity_create($entity_type, array('name' => 'test2', 'user_id' => $user1->id()));
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

    // Verify that all data got deleted.
    $definition = \Drupal::entityManager()->getDefinition($entity_type);
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {' . $definition->getBaseTable() . '}')->fetchField(), 'Base table was emptied');
    if ($data_table = $definition->getDataTable()) {
      $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {' . $data_table . '}')->fetchField(), 'Data table was emptied');
    }
    if ($revision_table = $definition->getRevisionTable()) {
      $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {' . $revision_table . '}')->fetchField(), 'Data table was emptied');
    }
  }

  /**
   * Tests that exceptions are thrown when saving or deleting an entity.
   */
  public function testEntityStorageExceptionHandling() {
    $entity = entity_create('entity_test', array('name' => 'test'));
    try {
      $GLOBALS['entity_test_throw_exception'] = TRUE;
      $entity->save();
      $this->fail('Entity presave EntityStorageException thrown but not caught.');
    }
    catch (EntityStorageException $e) {
      $this->assertEqual($e->getcode(), 1, 'Entity presave EntityStorageException caught.');
    }

    $entity = entity_create('entity_test', array('name' => 'test2'));
    try {
      unset($GLOBALS['entity_test_throw_exception']);
      $entity->save();
      $this->pass('Exception presave not thrown and not caught.');
    }
    catch (EntityStorageException $e) {
      $this->assertNotEqual($e->getCode(), 1, 'Entity presave EntityStorageException caught.');
    }

    $entity = entity_create('entity_test', array('name' => 'test3'));
    $entity->save();
    try {
      $GLOBALS['entity_test_throw_exception'] = TRUE;
      $entity->delete();
      $this->fail('Entity predelete EntityStorageException not thrown.');
    }
    catch (EntityStorageException $e) {
      $this->assertEqual($e->getCode(), 2, 'Entity predelete EntityStorageException caught.');
    }

    unset($GLOBALS['entity_test_throw_exception']);
    $entity = entity_create('entity_test', array('name' => 'test4'));
    $entity->save();
    try {
      $entity->delete();
      $this->pass('Entity predelete EntityStorageException not thrown and not caught.');
    }
    catch (EntityStorageException $e) {
      $this->assertNotEqual($e->getCode(), 2, 'Entity predelete EntityStorageException thrown.');
    }
  }
}
