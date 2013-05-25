<?php

/**
 * @file
 * Definition of Drupal\field\Tests\BulkDeleteTest.
 */

namespace Drupal\field\Tests;

use Drupal\field\Plugin\Core\Entity\FieldInstance;

use Drupal\Core\Language\Language;

/**
 * Unit test class for field bulk delete and batch purge functionality.
 */
class BulkDeleteTest extends FieldUnitTestBase {

  protected $field;

  public static function getInfo() {
    return array(
      'name' => 'Field bulk delete tests',
      'description' => 'Bulk delete fields and instances, and clean up afterwards.',
      'group' => 'Field API',
    );
  }

  /**
   * Converts the passed entities to partially created ones.
   *
   * This replicates the partial entities created in field_purge_data_batch(),
   * which only have the ids and the to be deleted field defined.
   *
   * @param $entities
   *   An array of entities of type test_entity.
   * @param $field_name
   *   A field name whose data should be copied from $entities into the returned
   *   partial entities.
   * @return
   *   An array of partial entities corresponding to $entities.
   */
  protected function convertToPartialEntities($entities, $field_name) {
    $partial_entities = array();
    foreach ($entities as $id => $entity) {
      // Re-create the entity to match what is expected
      // _field_create_entity_from_ids().
      $ids = (object) array(
        'entity_id' => $entity->ftid,
        'revision_id' => $entity->ftvid,
        'bundle' => $entity->fttype,
        'entity_type' => 'test_entity',
      );
      $partial_entities[$id] = _field_create_entity_from_ids($ids);
      $partial_entities[$id]->$field_name = $entity->$field_name;
    }
    return $partial_entities;
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
          // $entity is sometimes the first and sometimes the second argument.
          if ($actual_arguments[0] == $argument || $actual_arguments[1] == $argument) {
            $found = TRUE;
            break;
          }
        }
        $this->assertTrue($found, "$hook() was called on expected argument");
      }
    }
  }

  function setUp() {
    parent::setUp();

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
    $id = 1;
    $this->entity_type = 'test_entity';
    foreach ($this->bundles as $bundle) {
      foreach ($this->fields as $field) {
        $instance = array(
          'field_name' => $field['field_name'],
          'entity_type' => $this->entity_type,
          'bundle' => $bundle,
        );
        $this->instances[] = field_create_instance($instance);
      }

      for ($i = 0; $i < 10; $i++) {
        $entity = field_test_create_entity($id, $id, $bundle);
        foreach ($this->fields as $field) {
          $entity->{$field['field_name']}[Language::LANGCODE_NOT_SPECIFIED] = $this->_generateTestFieldValues($field['cardinality']);
        }
        $entity->save();
        $id++;
      }
    }
    $this->entities = entity_load_multiple($this->entity_type, range(1, $id));
    foreach ($this->entities as $entity) {
      // Also keep track of the entities per bundle.
      $this->entities_by_bundles[$entity->fttype][$entity->ftid] = $entity;
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
    $field_name = $field['field_name'];
    $factory = \Drupal::service('entity.query');

    // There are 10 entities of this bundle.
    $found = $factory->get('test_entity')
      ->condition('fttype', $bundle)
      ->execute();
    $this->assertEqual(count($found), 10, 'Correct number of entities found before deleting');

    // Delete the instance.
    $instance = field_info_instance($this->entity_type, $field['field_name'], $bundle);
    field_delete_instance($instance);

    // The instance still exists, deleted.
    $instances = field_read_instances(array('field_id' => $field['uuid'], 'deleted' => TRUE), array('include_deleted' => TRUE, 'include_inactive' => TRUE));
    $this->assertEqual(count($instances), 1, 'There is one deleted instance');
    $this->assertEqual($instances[0]['bundle'], $bundle, 'The deleted instance is for the correct bundle');

    // There are 0 entities of this bundle with non-deleted data.
    $found = $factory->get('test_entity')
      ->condition('fttype', $bundle)
      ->condition("$field_name.deleted", 0)
      ->execute();
    $this->assertFalse($found, 'No entities found after deleting');

    // There are 10 entities of this bundle when deleted fields are allowed, and
    // their values are correct.
    $found = $factory->get('test_entity')
      ->condition('fttype', $bundle)
      ->condition("$field_name.deleted", 1)
      ->sort('ftid')
      ->execute();
    $ids = (object) array(
      'entity_type' => 'test_entity',
      'bundle' => $bundle,
    );
    $entities = array();
    foreach ($found as $entity_id) {
      $ids->entity_id = $entity_id;
      $entities[$entity_id] = _field_create_entity_from_ids($ids);
    }
    field_attach_load($this->entity_type, $entities, FIELD_LOAD_CURRENT, array('field_id' => $field['uuid'], 'deleted' => TRUE));
    $this->assertEqual(count($found), 10, 'Correct number of entities found after deleting');
    foreach ($entities as $id => $entity) {
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
      $found = \Drupal::entityQuery('test_entity')
        ->condition('fttype', $bundle)
        ->condition($field['field_name'] . '.deleted', 1)
        ->execute();
      $this->assertEqual(count($found), $count, 'Correct number of entities found after purging 2');
    }

    // Check hooks invocations.
    // - hook_field_load() (multiple hook) should have been called on all
    // entities by pairs of two.
    // - hook_field_delete() should have been called once for each entity in the
    // bundle.
    $actual_hooks = field_test_memorize();
    $hooks = array();
    $entities = $this->convertToPartialEntities($this->entities_by_bundles[$bundle], $field['field_name']);
    foreach (array_chunk($entities, $batch_size, TRUE) as $chunk_entity) {
      $hooks['field_test_field_load'][] = $chunk_entity;
    }
    foreach ($entities as $entity) {
      $hooks['field_test_field_delete'][] = $entity;
    }
    $this->checkHooksInvocations($hooks, $actual_hooks);

    // The instance still exists, deleted.
    $instances = field_read_instances(array('field_id' => $field['uuid'], 'deleted' => TRUE), array('include_deleted' => TRUE, 'include_inactive' => TRUE));
    $this->assertEqual(count($instances), 1, 'There is one deleted instance');

    // Purge the instance.
    field_purge_batch($batch_size);

    // The instance is gone.
    $instances = field_read_instances(array('field_id' => $field['uuid'], 'deleted' => TRUE), array('include_deleted' => TRUE, 'include_inactive' => TRUE));
    $this->assertEqual(count($instances), 0, 'The instance is gone');

    // The field still exists, not deleted, because it has a second instance.
    $fields = field_read_fields(array('uuid' => $field['uuid']), array('include_deleted' => TRUE, 'include_inactive' => TRUE));
    $this->assertTrue(isset($fields[$field['uuid']]), 'The field exists and is not deleted');
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
    $entities = $this->convertToPartialEntities($this->entities_by_bundles[$bundle], $field['field_name']);
    $hooks['field_test_field_load'][] = $entities;
    $hooks['field_test_field_delete'] = $entities;
    $this->checkHooksInvocations($hooks, $actual_hooks);

    // Purge again to purge the instance.
    field_purge_batch(0);

    // The field still exists, not deleted.
    $fields = field_read_fields(array('uuid' => $field['uuid']), array('include_deleted' => TRUE));
    $this->assertTrue(isset($fields[$field['uuid']]) && !$fields[$field['uuid']]->deleted, 'The field exists and is not deleted');

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
    $entities = $this->convertToPartialEntities($this->entities_by_bundles[$bundle], $field['field_name']);
    $hooks['field_test_field_load'][] = $entities;
    $hooks['field_test_field_delete'] = $entities;
    $this->checkHooksInvocations($hooks, $actual_hooks);

    // The field still exists, deleted.
    $fields = field_read_fields(array('uuid' => $field['uuid']), array('include_deleted' => TRUE));
    $this->assertTrue(isset($fields[$field['uuid']]) && $fields[$field['uuid']]->deleted, 'The field exists and is deleted');

    // Purge again to purge the instance and the field.
    field_purge_batch(0);

    // The field is gone.
    $fields = field_read_fields(array('uuid' => $field['uuid']), array('include_deleted' => TRUE, 'include_inactive' => TRUE));
    $this->assertEqual(count($fields), 0, 'The field is purged.');
  }
}
