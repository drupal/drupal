<?php

/**
 * @file
 * Definition of Drupal\field\Tests\BulkDeleteTest.
 */

namespace Drupal\field\Tests;

use Drupal\entity\EntityFieldQuery;

/**
 * Unit test class for field bulk delete and batch purge functionality.
 */
class BulkDeleteTest extends FieldTestBase {
  protected $field;

  public static function getInfo() {
    return array(
      'name' => 'Field bulk delete tests',
      'description' => 'Bulk delete fields and instances, and clean up afterwards.',
      'group' => 'Field API',
    );
  }

  /**
   * Convenience function for Field API tests.
   *
   * Given an array of potentially fully-populated entities and an
   * optional field name, generate an array of stub entities of the
   * same fieldable type which contains the data for the field name
   * (if given).
   *
   * @param $entity_type
   *   The entity type of $entities.
   * @param $entities
   *   An array of entities of type $entity_type.
   * @param $field_name
   *   Optional; a field name whose data should be copied from
   *   $entities into the returned stub entities.
   * @return
   *   An array of stub entities corresponding to $entities.
   */
  function _generateStubEntities($entity_type, $entities, $field_name = NULL) {
    $stubs = array();
    foreach ($entities as $id => $entity) {
      $stub = entity_create_stub_entity($entity_type, entity_extract_ids($entity_type, $entity));
      if (isset($field_name)) {
        $stub->{$field_name} = $entity->{$field_name};
      }
      $stubs[$id] = $stub;
    }
    return $stubs;
  }

  /**
   * Tests that the expected hooks have been invoked on the expected entities.
   *
   * @param $expected_hooks
   *   An array keyed by hook name, with one entry per expected invocation.
   *   Each entry is the value of the "$entity" parameter the hook is expected
   *   to have been passed.
   * @param $actual_hooks
   *   The array of actual hook invocations recorded by field_test_memorize().
   */
  function checkHooksInvocations($expected_hooks, $actual_hooks) {
    foreach ($expected_hooks as $hook => $invocations) {
      $actual_invocations = $actual_hooks[$hook];

      // Check that the number of invocations is correct.
      $this->assertEqual(count($actual_invocations), count($invocations), "$hook() was called the expected number of times.");

      // Check that the hook was called for each expected argument.
      foreach ($invocations as $argument) {
        $found = FALSE;
        foreach ($actual_invocations as $actual_arguments) {
          if ($actual_arguments[1] == $argument) {
            $found = TRUE;
            break;
          }
        }
        $this->assertTrue($found, "$hook() was called on expected argument");
      }
    }
  }

  function setUp() {
    parent::setUp('field_test');

    $this->fields = array();
    $this->instances = array();
    $this->entities = array();
    $this->entities_by_bundles = array();

    // Create two bundles.
    $this->bundles = array('bb_1' => 'bb_1', 'bb_2' => 'bb_2');
    foreach ($this->bundles as $name => $desc) {
      field_test_create_bundle($name, $desc);
    }

    // Create two fields.
    $field = array('field_name' => 'bf_1', 'type' => 'test_field', 'cardinality' => 1);
    $this->fields[] = field_create_field($field);
    $field = array('field_name' => 'bf_2', 'type' => 'test_field', 'cardinality' => 4);
    $this->fields[] = field_create_field($field);

    // For each bundle, create an instance of each field, and 10
    // entities with values for each field.
    $id = 0;
    $this->entity_type = 'test_entity';
    foreach ($this->bundles as $bundle) {
      foreach ($this->fields as $field) {
        $instance = array(
          'field_name' => $field['field_name'],
          'entity_type' => $this->entity_type,
          'bundle' => $bundle,
          'widget' => array(
            'type' => 'test_field_widget',
          )
        );
        $this->instances[] = field_create_instance($instance);
      }

      for ($i = 0; $i < 10; $i++) {
        $entity = field_test_create_stub_entity($id, $id, $bundle);
        foreach ($this->fields as $field) {
          $entity->{$field['field_name']}[LANGUAGE_NOT_SPECIFIED] = $this->_generateTestFieldValues($field['cardinality']);
        }

        $this->entities[$id] = $entity;
        // Also keep track of the entities per bundle.
        $this->entities_by_bundles[$bundle][$id] = $entity;
        field_attach_insert($this->entity_type, $entity);
        $id++;
      }
    }
  }

  /**
   * Verify that deleting an instance leaves the field data items in
   * the database and that the appropriate Field API functions can
   * operate on the deleted data and instance.
   *
   * This tests how EntityFieldQuery interacts with
   * field_delete_instance() and could be moved to FieldCrudTestCase,
   * but depends on this class's setUp().
   */
  function testDeleteFieldInstance() {
    $bundle = reset($this->bundles);
    $field = reset($this->fields);

    // There are 10 entities of this bundle.
    $query = new EntityFieldQuery();
    $found = $query
      ->fieldCondition($field)
      ->entityCondition('bundle', $bundle)
      ->execute();
    $this->assertEqual(count($found['test_entity']), 10, 'Correct number of entities found before deleting');

    // Delete the instance.
    $instance = field_info_instance($this->entity_type, $field['field_name'], $bundle);
    field_delete_instance($instance);

    // The instance still exists, deleted.
    $instances = field_read_instances(array('field_id' => $field['id'], 'deleted' => 1), array('include_deleted' => 1, 'include_inactive' => 1));
    $this->assertEqual(count($instances), 1, 'There is one deleted instance');
    $this->assertEqual($instances[0]['bundle'], $bundle, 'The deleted instance is for the correct bundle');

    // There are 0 entities of this bundle with non-deleted data.
    $query = new EntityFieldQuery();
    $found = $query
      ->fieldCondition($field)
      ->entityCondition('bundle', $bundle)
      ->execute();
    $this->assertTrue(!isset($found['test_entity']), 'No entities found after deleting');

    // There are 10 entities of this bundle when deleted fields are allowed, and
    // their values are correct.
    $query = new EntityFieldQuery();
    $found = $query
      ->fieldCondition($field)
      ->entityCondition('bundle', $bundle)
      ->deleted(TRUE)
      ->execute();
    field_attach_load($this->entity_type, $found[$this->entity_type], FIELD_LOAD_CURRENT, array('field_id' => $field['id'], 'deleted' => 1));
    $this->assertEqual(count($found['test_entity']), 10, 'Correct number of entities found after deleting');
    foreach ($found['test_entity'] as $id => $entity) {
      $this->assertEqual($this->entities[$id]->{$field['field_name']}, $entity->{$field['field_name']}, "Entity $id with deleted data loaded correctly");
    }
  }

  /**
   * Verify that field data items and instances are purged when an
   * instance is deleted.
   */
  function testPurgeInstance() {
    // Start recording hook invocations.
    field_test_memorize();

    $bundle = reset($this->bundles);
    $field = reset($this->fields);

    // Delete the instance.
    $instance = field_info_instance($this->entity_type, $field['field_name'], $bundle);
    field_delete_instance($instance);

    // No field hooks were called.
    $mem = field_test_memorize();
    $this->assertEqual(count($mem), 0, 'No field hooks were called');

    $batch_size = 2;
    for ($count = 8; $count >= 0; $count -= $batch_size) {
      // Purge two entities.
      field_purge_batch($batch_size);

      // There are $count deleted entities left.
      $query = new EntityFieldQuery();
      $found = $query
        ->fieldCondition($field)
        ->entityCondition('bundle', $bundle)
        ->deleted(TRUE)
        ->execute();
      $this->assertEqual($count ? count($found['test_entity']) : count($found), $count, 'Correct number of entities found after purging 2');
    }

    // Check hooks invocations.
    // - hook_field_load() (multiple hook) should have been called on all
    // entities by pairs of two.
    // - hook_field_delete() should have been called once for each entity in the
    // bundle.
    $actual_hooks = field_test_memorize();
    $hooks = array();
    $stubs = $this->_generateStubEntities($this->entity_type, $this->entities_by_bundles[$bundle], $field['field_name']);
    foreach (array_chunk($stubs, $batch_size, TRUE) as $chunk) {
      $hooks['field_test_field_load'][] = $chunk;
    }
    foreach ($stubs as $stub) {
      $hooks['field_test_field_delete'][] = $stub;
    }
    $this->checkHooksInvocations($hooks, $actual_hooks);

    // The instance still exists, deleted.
    $instances = field_read_instances(array('field_id' => $field['id'], 'deleted' => 1), array('include_deleted' => 1, 'include_inactive' => 1));
    $this->assertEqual(count($instances), 1, 'There is one deleted instance');

    // Purge the instance.
    field_purge_batch($batch_size);

    // The instance is gone.
    $instances = field_read_instances(array('field_id' => $field['id'], 'deleted' => 1), array('include_deleted' => 1, 'include_inactive' => 1));
    $this->assertEqual(count($instances), 0, 'The instance is gone');

    // The field still exists, not deleted, because it has a second instance.
    $fields = field_read_fields(array('id' => $field['id']), array('include_deleted' => 1, 'include_inactive' => 1));
    $this->assertTrue(isset($fields[$field['id']]), 'The field exists and is not deleted');
  }

  /**
   * Verify that fields are preserved and purged correctly as multiple
   * instances are deleted and purged.
   */
  function testPurgeField() {
    // Start recording hook invocations.
    field_test_memorize();

    $field = reset($this->fields);

    // Delete the first instance.
    $bundle = reset($this->bundles);
    $instance = field_info_instance($this->entity_type, $field['field_name'], $bundle);
    field_delete_instance($instance);

    // Assert that hook_field_delete() was not called yet.
    $mem = field_test_memorize();
    $this->assertEqual(count($mem), 0, 'No field hooks were called.');

    // Purge the data.
    field_purge_batch(10);

    // Check hooks invocations.
    // - hook_field_load() (multiple hook) should have been called once, for all
    // entities in the bundle.
    // - hook_field_delete() should have been called once for each entity in the
    // bundle.
    $actual_hooks = field_test_memorize();
    $hooks = array();
    $stubs = $this->_generateStubEntities($this->entity_type, $this->entities_by_bundles[$bundle], $field['field_name']);
    $hooks['field_test_field_load'][] = $stubs;
    foreach ($stubs as $stub) {
      $hooks['field_test_field_delete'][] = $stub;
    }
    $this->checkHooksInvocations($hooks, $actual_hooks);

    // Purge again to purge the instance.
    field_purge_batch(0);

    // The field still exists, not deleted.
    $fields = field_read_fields(array('id' => $field['id']), array('include_deleted' => 1));
    $this->assertTrue(isset($fields[$field['id']]) && !$fields[$field['id']]['deleted'], 'The field exists and is not deleted');

    // Delete the second instance.
    $bundle = next($this->bundles);
    $instance = field_info_instance($this->entity_type, $field['field_name'], $bundle);
    field_delete_instance($instance);

    // Assert that hook_field_delete() was not called yet.
    $mem = field_test_memorize();
    $this->assertEqual(count($mem), 0, 'No field hooks were called.');

    // Purge the data.
    field_purge_batch(10);

    // Check hooks invocations (same as above, for the 2nd bundle).
    $actual_hooks = field_test_memorize();
    $hooks = array();
    $stubs = $this->_generateStubEntities($this->entity_type, $this->entities_by_bundles[$bundle], $field['field_name']);
    $hooks['field_test_field_load'][] = $stubs;
    foreach ($stubs as $stub) {
      $hooks['field_test_field_delete'][] = $stub;
    }
    $this->checkHooksInvocations($hooks, $actual_hooks);

    // The field still exists, deleted.
    $fields = field_read_fields(array('id' => $field['id']), array('include_deleted' => 1));
    $this->assertTrue(isset($fields[$field['id']]) && $fields[$field['id']]['deleted'], 'The field exists and is deleted');

    // Purge again to purge the instance and the field.
    field_purge_batch(0);

    // The field is gone.
    $fields = field_read_fields(array('id' => $field['id']), array('include_deleted' => 1, 'include_inactive' => 1));
    $this->assertEqual(count($fields), 0, 'The field is purged.');
  }
}
