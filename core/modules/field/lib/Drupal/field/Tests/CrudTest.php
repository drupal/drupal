<?php

/**
 * @file
 * Definition of Drupal\field\Tests\CrudTest.
 */

namespace Drupal\field\Tests;

use Drupal\field\FieldException;
use Exception;

class CrudTest extends FieldTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Field CRUD tests',
      'description' => 'Test field create, read, update, and delete.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    // field_update_field() tests use number.module
    parent::setUp('field_test', 'number');
  }

  // TODO : test creation with
  // - a full fledged $field structure, check that all the values are there
  // - a minimal $field structure, check all default values are set
  // defer actual $field comparison to a helper function, used for the two cases above

  /**
   * Test the creation of a field.
   */
  function testCreateField() {
    $field_definition = array(
      'field_name' => 'field_2',
      'type' => 'test_field',
    );
    field_test_memorize();
    $field_definition = field_create_field($field_definition);
    $mem = field_test_memorize();
    $this->assertIdentical($mem['field_test_field_create_field'][0][0], $field_definition, 'hook_field_create_field() called with correct arguments.');

    // Read the raw record from the {field_config_instance} table.
    $result = db_query('SELECT * FROM {field_config} WHERE field_name = :field_name', array(':field_name' => $field_definition['field_name']));
    $record = $result->fetchAssoc();
    $record['data'] = unserialize($record['data']);

    // Ensure that basic properties are preserved.
    $this->assertEqual($record['field_name'], $field_definition['field_name'], t('The field name is properly saved.'));
    $this->assertEqual($record['type'], $field_definition['type'], t('The field type is properly saved.'));

    // Ensure that cardinality defaults to 1.
    $this->assertEqual($record['cardinality'], 1, t('Cardinality defaults to 1.'));

    // Ensure that default settings are present.
    $field_type = field_info_field_types($field_definition['type']);
    $this->assertIdentical($record['data']['settings'], $field_type['settings'], t('Default field settings have been written.'));

    // Ensure that default storage was set.
    $this->assertEqual($record['storage_type'], variable_get('field_storage_default'), t('The field type is properly saved.'));

    // Guarantee that the name is unique.
    try {
      field_create_field($field_definition);
      $this->fail(t('Cannot create two fields with the same name.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create two fields with the same name.'));
    }

    // Check that field type is required.
    try {
      $field_definition = array(
        'field_name' => 'field_1',
      );
      field_create_field($field_definition);
      $this->fail(t('Cannot create a field with no type.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with no type.'));
    }

    // Check that field name is required.
    try {
      $field_definition = array(
        'type' => 'test_field'
      );
      field_create_field($field_definition);
      $this->fail(t('Cannot create an unnamed field.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create an unnamed field.'));
    }

    // Check that field name must start with a letter or _.
    try {
      $field_definition = array(
        'field_name' => '2field_2',
        'type' => 'test_field',
      );
      field_create_field($field_definition);
      $this->fail(t('Cannot create a field with a name starting with a digit.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name starting with a digit.'));
    }

    // Check that field name must only contain lowercase alphanumeric or _.
    try {
      $field_definition = array(
        'field_name' => 'field#_3',
        'type' => 'test_field',
      );
      field_create_field($field_definition);
      $this->fail(t('Cannot create a field with a name containing an illegal character.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name containing an illegal character.'));
    }

    // Check that field name cannot be longer than 32 characters long.
    try {
      $field_definition = array(
        'field_name' => '_12345678901234567890123456789012',
        'type' => 'test_field',
      );
      field_create_field($field_definition);
      $this->fail(t('Cannot create a field with a name longer than 32 characters.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name longer than 32 characters.'));
    }

    // Check that field name can not be an entity key.
    // "ftvid" is known as an entity key from the "test_entity" type.
    try {
      $field_definition = array(
        'type' => 'test_field',
        'field_name' => 'ftvid',
      );
      $field = field_create_field($field_definition);
      $this->fail(t('Cannot create a field bearing the name of an entity key.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field bearing the name of an entity key.'));
    }
  }

  /**
   * Test failure to create a field.
   */
  function testCreateFieldFail() {
    $field_name = 'duplicate';
    $field_definition = array('field_name' => $field_name, 'type' => 'test_field', 'storage' => array('type' => 'field_test_storage_failure'));
    $query = db_select('field_config')->condition('field_name', $field_name)->countQuery();

    // The field does not appear in field_config.
    $count = $query->execute()->fetchField();
    $this->assertEqual($count, 0, 'A field_config row for the field does not exist.');

    // Try to create the field.
    try {
      $field = field_create_field($field_definition);
      $this->assertTrue(FALSE, 'Field creation (correctly) fails.');
    }
    catch (Exception $e) {
      $this->assertTrue(TRUE, 'Field creation (correctly) fails.');
    }

    // The field does not appear in field_config.
    $count = $query->execute()->fetchField();
    $this->assertEqual($count, 0, 'A field_config row for the field does not exist.');
  }

  /**
   * Test reading back a field definition.
   */
  function testReadField() {
    $field_definition = array(
      'field_name' => 'field_1',
      'type' => 'test_field',
    );
    field_create_field($field_definition);

    // Read the field back.
    $field = field_read_field($field_definition['field_name']);
    $this->assertTrue($field_definition < $field, t('The field was properly read.'));
  }

  /**
   * Test creation of indexes on data column.
   */
  function testFieldIndexes() {
    // Check that indexes specified by the field type are used by default.
    $field_definition = array(
      'field_name' => 'field_1',
      'type' => 'test_field',
    );
    field_create_field($field_definition);
    $field = field_read_field($field_definition['field_name']);
    $expected_indexes = array('value' => array('value'));
    $this->assertEqual($field['indexes'], $expected_indexes, t('Field type indexes saved by default'));

    // Check that indexes specified by the field definition override the field
    // type indexes.
    $field_definition = array(
      'field_name' => 'field_2',
      'type' => 'test_field',
      'indexes' => array(
        'value' => array(),
      ),
    );
    field_create_field($field_definition);
    $field = field_read_field($field_definition['field_name']);
    $expected_indexes = array('value' => array());
    $this->assertEqual($field['indexes'], $expected_indexes, t('Field definition indexes override field type indexes'));

    // Check that indexes specified by the field definition add to the field
    // type indexes.
    $field_definition = array(
      'field_name' => 'field_3',
      'type' => 'test_field',
      'indexes' => array(
        'value_2' => array('value'),
      ),
    );
    field_create_field($field_definition);
    $field = field_read_field($field_definition['field_name']);
    $expected_indexes = array('value' => array('value'), 'value_2' => array('value'));
    $this->assertEqual($field['indexes'], $expected_indexes, t('Field definition indexes are merged with field type indexes'));
  }

  /**
   * Test the deletion of a field.
   */
  function testDeleteField() {
    // TODO: Also test deletion of the data stored in the field ?

    // Create two fields (so we can test that only one is deleted).
    $this->field = array('field_name' => 'field_1', 'type' => 'test_field');
    field_create_field($this->field);
    $this->another_field = array('field_name' => 'field_2', 'type' => 'test_field');
    field_create_field($this->another_field);

    // Create instances for each.
    $this->instance_definition = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'widget' => array(
        'type' => 'test_field_widget',
      ),
    );
    field_create_instance($this->instance_definition);
    $this->another_instance_definition = $this->instance_definition;
    $this->another_instance_definition['field_name'] = $this->another_field['field_name'];
    field_create_instance($this->another_instance_definition);

    // Test that the first field is not deleted, and then delete it.
    $field = field_read_field($this->field['field_name'], array('include_deleted' => TRUE));
    $this->assertTrue(!empty($field) && empty($field['deleted']), t('A new field is not marked for deletion.'));
    field_delete_field($this->field['field_name']);

    // Make sure that the field is marked as deleted when it is specifically
    // loaded.
    $field = field_read_field($this->field['field_name'], array('include_deleted' => TRUE));
    $this->assertTrue(!empty($field['deleted']), t('A deleted field is marked for deletion.'));

    // Make sure that this field's instance is marked as deleted when it is
    // specifically loaded.
    $instance = field_read_instance('test_entity', $this->instance_definition['field_name'], $this->instance_definition['bundle'], array('include_deleted' => TRUE));
    $this->assertTrue(!empty($instance['deleted']), t('An instance for a deleted field is marked for deletion.'));

    // Try to load the field normally and make sure it does not show up.
    $field = field_read_field($this->field['field_name']);
    $this->assertTrue(empty($field), t('A deleted field is not loaded by default.'));

    // Try to load the instance normally and make sure it does not show up.
    $instance = field_read_instance('test_entity', $this->instance_definition['field_name'], $this->instance_definition['bundle']);
    $this->assertTrue(empty($instance), t('An instance for a deleted field is not loaded by default.'));

    // Make sure the other field (and its field instance) are not deleted.
    $another_field = field_read_field($this->another_field['field_name']);
    $this->assertTrue(!empty($another_field) && empty($another_field['deleted']), t('A non-deleted field is not marked for deletion.'));
    $another_instance = field_read_instance('test_entity', $this->another_instance_definition['field_name'], $this->another_instance_definition['bundle']);
    $this->assertTrue(!empty($another_instance) && empty($another_instance['deleted']), t('An instance of a non-deleted field is not marked for deletion.'));

    // Try to create a new field the same name as a deleted field and
    // write data into it.
    field_create_field($this->field);
    field_create_instance($this->instance_definition);
    $field = field_read_field($this->field['field_name']);
    $this->assertTrue(!empty($field) && empty($field['deleted']), t('A new field with a previously used name is created.'));
    $instance = field_read_instance('test_entity', $this->instance_definition['field_name'], $this->instance_definition['bundle']);
    $this->assertTrue(!empty($instance) && empty($instance['deleted']), t('A new instance for a previously used field name is created.'));

    // Save an entity with data for the field
    $entity = field_test_create_stub_entity(0, 0, $instance['bundle']);
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $values[0]['value'] = mt_rand(1, 127);
    $entity->{$field['field_name']}[$langcode] = $values;
    $entity_type = 'test_entity';
    field_attach_insert('test_entity', $entity);

    // Verify the field is present on load
    $entity = field_test_create_stub_entity(0, 0, $this->instance_definition['bundle']);
    field_attach_load($entity_type, array(0 => $entity));
    $this->assertIdentical(count($entity->{$field['field_name']}[$langcode]), count($values), "Data in previously deleted field saves and loads correctly");
    foreach ($values as $delta => $value) {
      $this->assertEqual($entity->{$field['field_name']}[$langcode][$delta]['value'], $values[$delta]['value'], "Data in previously deleted field saves and loads correctly");
    }
  }

  function testUpdateNonExistentField() {
    $test_field = array('field_name' => 'does_not_exist', 'type' => 'number_decimal');
    try {
      field_update_field($test_field);
      $this->fail(t('Cannot update a field that does not exist.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot update a field that does not exist.'));
    }
  }

  function testUpdateFieldType() {
    $field = array('field_name' => 'field_type', 'type' => 'number_decimal');
    $field = field_create_field($field);

    $test_field = array('field_name' => 'field_type', 'type' => 'number_integer');
    try {
      field_update_field($test_field);
      $this->fail(t('Cannot update a field to a different type.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot update a field to a different type.'));
    }
  }

  /**
   * Test updating a field.
   */
  function testUpdateField() {
    // Create a field with a defined cardinality, so that we can ensure it's
    // respected. Since cardinality enforcement is consistent across database
    // systems, it makes a good test case.
    $cardinality = 4;
    $field_definition = array(
      'field_name' => 'field_update',
      'type' => 'test_field',
      'cardinality' => $cardinality,
    );
    $field_definition = field_create_field($field_definition);
    $instance = array(
      'field_name' => 'field_update',
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
    );
    $instance = field_create_instance($instance);

    do {
      // We need a unique ID for our entity. $cardinality will do.
      $id = $cardinality;
      $entity = field_test_create_stub_entity($id, $id, $instance['bundle']);
      // Fill in the entity with more values than $cardinality.
      for ($i = 0; $i < 20; $i++) {
        $entity->field_update[LANGUAGE_NOT_SPECIFIED][$i]['value'] = $i;
      }
      // Save the entity.
      field_attach_insert('test_entity', $entity);
      // Load back and assert there are $cardinality number of values.
      $entity = field_test_create_stub_entity($id, $id, $instance['bundle']);
      field_attach_load('test_entity', array($id => $entity));
      $this->assertEqual(count($entity->field_update[LANGUAGE_NOT_SPECIFIED]), $field_definition['cardinality'], 'Cardinality is kept');
      // Now check the values themselves.
      for ($delta = 0; $delta < $cardinality; $delta++) {
        $this->assertEqual($entity->field_update[LANGUAGE_NOT_SPECIFIED][$delta]['value'], $delta, 'Value is kept');
      }
      // Increase $cardinality and set the field cardinality to the new value.
      $field_definition['cardinality'] = ++$cardinality;
      field_update_field($field_definition);
    } while ($cardinality < 6);
  }

  /**
   * Test field type modules forbidding an update.
   */
  function testUpdateFieldForbid() {
    $field = array('field_name' => 'forbidden', 'type' => 'test_field', 'settings' => array('changeable' => 0, 'unchangeable' => 0));
    $field = field_create_field($field);
    $field['settings']['changeable']++;
    try {
      field_update_field($field);
      $this->pass(t("A changeable setting can be updated."));
    }
    catch (FieldException $e) {
      $this->fail(t("An unchangeable setting cannot be updated."));
    }
    $field['settings']['unchangeable']++;
    try {
      field_update_field($field);
      $this->fail(t("An unchangeable setting can be updated."));
    }
    catch (FieldException $e) {
      $this->pass(t("An unchangeable setting cannot be updated."));
    }
  }

  /**
   * Test that fields are properly marked active or inactive.
   */
  function testActive() {
    $field_definition = array(
      'field_name' => 'field_1',
      'type' => 'test_field',
      // For this test, we need a storage backend provided by a different
      // module than field_test.module.
      'storage' => array(
        'type' => 'field_sql_storage',
      ),
    );
    field_create_field($field_definition);

    // Test disabling and enabling:
    // - the field type module,
    // - the storage module,
    // - both.
    $this->_testActiveHelper($field_definition, array('field_test'));
    $this->_testActiveHelper($field_definition, array('field_sql_storage'));
    $this->_testActiveHelper($field_definition, array('field_test', 'field_sql_storage'));
  }

  /**
   * Helper function for testActive().
   *
   * Test dependency between a field and a set of modules.
   *
   * @param $field_definition
   *   A field definition.
   * @param $modules
   *   An aray of module names. The field will be tested to be inactive as long
   *   as any of those modules is disabled.
   */
  function _testActiveHelper($field_definition, $modules) {
    $field_name = $field_definition['field_name'];

    // Read the field.
    $field = field_read_field($field_name);
    $this->assertTrue($field_definition <= $field, t('The field was properly read.'));

    module_disable($modules, FALSE);

    $fields = field_read_fields(array('field_name' => $field_name), array('include_inactive' => TRUE));
    $this->assertTrue(isset($fields[$field_name]) && $field_definition < $field, t('The field is properly read when explicitly fetching inactive fields.'));

    // Re-enable modules one by one, and check that the field is still inactive
    // while some modules remain disabled.
    while ($modules) {
      $field = field_read_field($field_name);
      $this->assertTrue(empty($field), t('%modules disabled. The field is marked inactive.', array('%modules' => implode(', ', $modules))));

      $module = array_shift($modules);
      module_enable(array($module), FALSE);
    }

    // Check that the field is active again after all modules have been
    // enabled.
    $field = field_read_field($field_name);
    $this->assertTrue($field_definition <= $field, t('The field was was marked active.'));
  }
}
