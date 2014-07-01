<?php

/**
 * @file
 * Contains \Drupal\field\Tests\CrudTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldException;

/**
 * Tests field CRUD operations.
 */
class CrudTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array();

  public static function getInfo() {
    return array(
      'name' => 'Field CRUD tests',
      'description' => 'Test field create, read, update, and delete.',
      'group' => 'Field API',
    );
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
      'name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    field_test_memorize();
    $field = entity_create('field_config', $field_definition);
    $field->save();
    $mem = field_test_memorize();
    $this->assertIdentical($mem['field_test_field_config_create'][0][0]->getName(), $field_definition['name'], 'hook_entity_create() called with correct arguments.');
    $this->assertIdentical($mem['field_test_field_config_create'][0][0]->getType(), $field_definition['type'], 'hook_entity_create() called with correct arguments.');

    // Read the configuration. Check against raw configuration data rather than
    // the loaded ConfigEntity, to be sure we check that the defaults are
    // applied on write.
    $field_config = \Drupal::config('field.field.' . $field->id())->get();

    // Ensure that basic properties are preserved.
    $this->assertEqual($field_config['name'], $field_definition['name'], 'The field name is properly saved.');
    $this->assertEqual($field_config['entity_type'], $field_definition['entity_type'], 'The field entity type is properly saved.');
    $this->assertEqual($field_config['id'], $field_definition['entity_type'] . '.' . $field_definition['name'], 'The field id is properly saved.');
    $this->assertEqual($field_config['type'], $field_definition['type'], 'The field type is properly saved.');

    // Ensure that cardinality defaults to 1.
    $this->assertEqual($field_config['cardinality'], 1, 'Cardinality defaults to 1.');

    // Ensure that default settings are present.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $this->assertEqual($field_config['settings'], $field_type_manager->getDefaultSettings($field_definition['type']), 'Default field settings have been written.');

    // Guarantee that the name is unique.
    try {
      entity_create('field_config', $field_definition)->save();
      $this->fail(t('Cannot create two fields with the same name.'));
    }
    catch (EntityStorageException $e) {
      $this->pass(t('Cannot create two fields with the same name.'));
    }

    // Check that field type is required.
    try {
      $field_definition = array(
        'name' => 'field_1',
        'entity_type' => 'entity_type',
      );
      entity_create('field_config', $field_definition)->save();
      $this->fail(t('Cannot create a field with no type.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with no type.'));
    }

    // Check that field name is required.
    try {
      $field_definition = array(
        'type' => 'test_field',
        'entity_type' => 'entity_test',
      );
      entity_create('field_config', $field_definition)->save();
      $this->fail(t('Cannot create an unnamed field.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create an unnamed field.'));
    }
    // Check that entity type is required.
    try {
      $field_definition = array(
        'name' => 'test_field',
        'type' => 'test_field'
      );
      entity_create('field_config', $field_definition)->save();
      $this->fail('Cannot create a field without an entity type.');
    }
    catch (FieldException $e) {
      $this->pass('Cannot create a field without an entity type.');
    }

    // Check that field name must start with a letter or _.
    try {
      $field_definition = array(
        'name' => '2field_2',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      );
      entity_create('field_config', $field_definition)->save();
      $this->fail(t('Cannot create a field with a name starting with a digit.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name starting with a digit.'));
    }

    // Check that field name must only contain lowercase alphanumeric or _.
    try {
      $field_definition = array(
        'name' => 'field#_3',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      );
      entity_create('field_config', $field_definition)->save();
      $this->fail(t('Cannot create a field with a name containing an illegal character.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name containing an illegal character.'));
    }

    // Check that field name cannot be longer than 32 characters long.
    try {
      $field_definition = array(
        'name' => '_12345678901234567890123456789012',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      );
      entity_create('field_config', $field_definition)->save();
      $this->fail(t('Cannot create a field with a name longer than 32 characters.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name longer than 32 characters.'));
    }

    // Check that field name can not be an entity key.
    // "id" is known as an entity key from the "entity_test" type.
    try {
      $field_definition = array(
        'type' => 'test_field',
        'name' => 'id',
        'entity_type' => 'entity_test',
      );
      entity_create('field_config', $field_definition)->save();
      $this->fail(t('Cannot create a field bearing the name of an entity key.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field bearing the name of an entity key.'));
    }
  }

  /**
   * Tests that an explicit schema can be provided on creation of a field.
   *
   * This behavior is needed to allow field creation within updates, since
   * plugin classes (and thus the field type schema) cannot be accessed.
   */
  function testCreateFieldWithExplicitSchema() {
    $field_definition = array(
      'name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'schema' => array(
        'dummy' => 'foobar'
      ),
    );
    $field = entity_create('field_config', $field_definition);
    $this->assertEqual($field->getSchema(), $field_definition['schema']);
  }

  /**
   * Tests reading field definitions.
   */
  function testReadFields() {
    $field_definition = array(
      'name' => 'field_1',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    $field = entity_create('field_config', $field_definition);
    $field->save();
    $id = $field->id();

    // Check that 'single column' criteria works.
    $fields = entity_load_multiple_by_properties('field_config', array('field_name' => $field_definition['name']));
    $this->assertTrue(count($fields) == 1 && isset($fields[$id]), 'The field was properly read.');

    // Check that 'multi column' criteria works.
    $fields = entity_load_multiple_by_properties('field_config', array('field_name' => $field_definition['name'], 'type' => $field_definition['type']));
    $this->assertTrue(count($fields) == 1 && isset($fields[$id]), 'The field was properly read.');
    $fields = entity_load_multiple_by_properties('field_config', array('field_name' => $field_definition['name'], 'type' => 'foo'));
    $this->assertTrue(empty($fields), 'No field was found.');

    // Create an instance of the field.
    $instance_definition = array(
      'field_name' => $field_definition['name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    entity_create('field_instance_config', $instance_definition)->save();
  }

  /**
   * Test creation of indexes on data column.
   */
  function testFieldIndexes() {
    // Check that indexes specified by the field type are used by default.
    $field_definition = array(
      'name' => 'field_1',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    $field = entity_create('field_config', $field_definition);
    $field->save();
    $field = entity_load('field_config', $field->id());
    $schema = $field->getSchema();
    $expected_indexes = array('value' => array('value'));
    $this->assertEqual($schema['indexes'], $expected_indexes, 'Field type indexes saved by default');

    // Check that indexes specified by the field definition override the field
    // type indexes.
    $field_definition = array(
      'name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'indexes' => array(
        'value' => array(),
      ),
    );
    $field = entity_create('field_config', $field_definition);
    $field->save();
    $field = entity_load('field_config', $field->id());
    $schema = $field->getSchema();
    $expected_indexes = array('value' => array());
    $this->assertEqual($schema['indexes'], $expected_indexes, 'Field definition indexes override field type indexes');

    // Check that indexes specified by the field definition add to the field
    // type indexes.
    $field_definition = array(
      'name' => 'field_3',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'indexes' => array(
        'value_2' => array('value'),
      ),
    );
    $field = entity_create('field_config', $field_definition);
    $field->save();
    $id = $field->id();
    $field = entity_load('field_config', $id);
    $schema = $field->getSchema();
    $expected_indexes = array('value' => array('value'), 'value_2' => array('value'));
    $this->assertEqual($schema['indexes'], $expected_indexes, 'Field definition indexes are merged with field type indexes');
  }

  /**
   * Test the deletion of a field.
   */
  function testDeleteField() {
    // TODO: Also test deletion of the data stored in the field ?

    // Create two fields (so we can test that only one is deleted).
    $this->field = array(
      'name' => 'field_1',
      'type' => 'test_field',
      'entity_type' => 'entity_test',
    );
    entity_create('field_config', $this->field)->save();
    $this->another_field = array(
      'name' => 'field_2',
      'type' => 'test_field',
      'entity_type' => 'entity_test',
    );
    entity_create('field_config', $this->another_field)->save();

    // Create instances for each.
    $this->instance_definition = array(
      'field_name' => $this->field['name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    entity_create('field_instance_config', $this->instance_definition)->save();
    $another_instance_definition = $this->instance_definition;
    $another_instance_definition['field_name'] = $this->another_field['name'];
    entity_create('field_instance_config', $another_instance_definition)->save();

    // Test that the first field is not deleted, and then delete it.
    $field = current(entity_load_multiple_by_properties('field_config', array('field_name' => $this->field['name'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($field) && empty($field->deleted), 'A new field is not marked for deletion.');
    FieldConfig::loadByName('entity_test', $this->field['name'])->delete();

    // Make sure that the field is marked as deleted when it is specifically
    // loaded.
    $field = current(entity_load_multiple_by_properties('field_config', array('field_name' => $this->field['name'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($field->deleted), 'A deleted field is marked for deletion.');

    // Make sure that this field's instance is marked as deleted when it is
    // specifically loaded.
    $instance = current(entity_load_multiple_by_properties('field_instance_config', array('entity_type' => 'entity_test', 'field_name' => $this->instance_definition['field_name'], 'bundle' => $this->instance_definition['bundle'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($instance->deleted), 'An instance for a deleted field is marked for deletion.');

    // Try to load the field normally and make sure it does not show up.
    $field = entity_load('field_config', 'entity_test.' . $this->field['name']);
    $this->assertTrue(empty($field), 'A deleted field is not loaded by default.');

    // Try to load the instance normally and make sure it does not show up.
    $instance = entity_load('field_instance_config', 'entity_test.' . '.' . $this->instance_definition['bundle'] . '.' . $this->instance_definition['field_name']);
    $this->assertTrue(empty($instance), 'An instance for a deleted field is not loaded by default.');

    // Make sure the other field (and its field instance) are not deleted.
    $another_field = entity_load('field_config', 'entity_test.' . $this->another_field['name']);
    $this->assertTrue(!empty($another_field) && empty($another_field->deleted), 'A non-deleted field is not marked for deletion.');
    $another_instance = entity_load('field_instance_config', 'entity_test.' . $another_instance_definition['bundle'] . '.' . $another_instance_definition['field_name']);
    $this->assertTrue(!empty($another_instance) && empty($another_instance->deleted), 'An instance of a non-deleted field is not marked for deletion.');

    // Try to create a new field the same name as a deleted field and
    // write data into it.
    entity_create('field_config', $this->field)->save();
    entity_create('field_instance_config', $this->instance_definition)->save();
    $field = entity_load('field_config', 'entity_test.' . $this->field['name']);
    $this->assertTrue(!empty($field) && empty($field->deleted), 'A new field with a previously used name is created.');
    $instance = entity_load('field_instance_config', 'entity_test.' . $this->instance_definition['bundle'] . '.' . $this->instance_definition['field_name'] );
    $this->assertTrue(!empty($instance) && empty($instance->deleted), 'A new instance for a previously used field name is created.');

    // Save an entity with data for the field
    $entity = entity_create('entity_test');
    $values[0]['value'] = mt_rand(1, 127);
    $entity->{$field->getName()}->value = $values[0]['value'];
    $entity = $this->entitySaveReload($entity);

    // Verify the field is present on load
    $this->assertIdentical(count($entity->{$field->getName()}), count($values), "Data in previously deleted field saves and loads correctly");
    foreach ($values as $delta => $value) {
      $this->assertEqual($entity->{$field->getName()}[$delta]->value, $values[$delta]['value'], "Data in previously deleted field saves and loads correctly");
    }
  }

  function testUpdateFieldType() {
    $field_definition = array(
      'name' => 'field_type',
      'entity_type' => 'entity_test',
      'type' => 'decimal',
    );
    $field = entity_create('field_config', $field_definition);
    $field->save();

    try {
      $field->type = 'integer';
      $field->save();
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
    $field = entity_create('field_config', array(
      'name' => 'field_update',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => $cardinality,
    ));
    $field->save();
    $instance = entity_create('field_instance_config', array(
      'field' => $field,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ));
    $instance->save();

    do {
      $entity = entity_create('entity_test');
      // Fill in the entity with more values than $cardinality.
      for ($i = 0; $i < 20; $i++) {
        // We can not use $i here because 0 values are filtered out.
        $entity->field_update[$i]->value = $i + 1;
      }
      // Load back and assert there are $cardinality number of values.
      $entity = $this->entitySaveReload($entity);
      $this->assertEqual(count($entity->field_update), $field->cardinality);
      // Now check the values themselves.
      for ($delta = 0; $delta < $cardinality; $delta++) {
        $this->assertEqual($entity->field_update[$delta]->value, $delta + 1);
      }
      // Increase $cardinality and set the field cardinality to the new value.
      $field->cardinality = ++$cardinality;
      $field->save();
    } while ($cardinality < 6);
  }

  /**
   * Test field type modules forbidding an update.
   */
  function testUpdateFieldForbid() {
    $field = entity_create('field_config', array(
      'name' => 'forbidden',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'settings' => array(
        'changeable' => 0,
        'unchangeable' => 0
    )));
    $field->save();
    $field->settings['changeable']++;
    try {
      $field->save();
      $this->pass(t("A changeable setting can be updated."));
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->fail(t("An unchangeable setting cannot be updated."));
    }
    $field->settings['unchangeable']++;
    try {
      $field->save();
      $this->fail(t("An unchangeable setting can be updated."));
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass(t("An unchangeable setting cannot be updated."));
    }
  }

}
