<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldAttachStorageTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\Language;

/**
 * Unit test class for storage-related field_attach_* functions.
 *
 * All field_attach_* test work with all field_storage plugins and
 * all hook_field_attach_pre_{load,insert,update}() hooks.
 */
class FieldAttachStorageTest extends FieldUnitTestBase {

  /**
   * The field instance.
   *
   * @var \Drupal\field\Entity\FieldInstance
   */
  protected $instance;

  /**
   * Field name to use in the test.
   *
   * @var string
   */
  protected $field_name;

  public static function getInfo() {
    return array(
      'name' => 'Field attach tests (storage-related)',
      'description' => 'Test storage-related Field Attach API functions.',
      'group' => 'Field API',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installSchema('entity_test', array('entity_test_rev', 'entity_test_rev_revision'));
  }

  /**
   * Check field values insert, update and load.
   *
   * Works independently of the underlying field storage backend. Inserts or
   * updates random field data and then loads and verifies the data.
   */
  function testFieldAttachSaveLoad() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);

    // Configure the instance so that we test hook_field_load() (see
    // field_test_field_load() in field_test.module).
    $this->instance['settings']['test_hook_field_load'] = TRUE;
    $this->instance->save();

    // TODO : test empty values filtering and "compression" (store consecutive deltas).
    // Preparation: create three revisions and store them in $revision array.
    $values = array();
    for ($revision_id = 0; $revision_id < 3; $revision_id++) {
      $revision[$revision_id] = entity_create($entity_type, array('id' => 0, 'revision_id' => $revision_id));
      // Note: we try to insert one extra value.
      $values[$revision_id] = $this->_generateTestFieldValues($this->field['cardinality'] + 1);
      $current_revision = $revision_id;
      // If this is the first revision do an insert.
      if (!$revision_id) {
        $revision[$revision_id]->{$this->field_name}->setValue($values[$revision_id]);
        field_attach_insert($revision[$revision_id]);
      }
      else {
        // Otherwise do an update.
        $revision[$revision_id]->{$this->field_name}->setValue($values[$revision_id]);
        field_attach_update($revision[$revision_id]);
      }
    }

    // Confirm current revision loads the correct data.
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0));
    field_attach_load($entity_type, array(0 => $entity));
    // Number of values per field loaded equals the field cardinality.
    $this->assertEqual(count($entity->{$this->field_name}), $this->field['cardinality'], 'Current revision: expected number of values');
    for ($delta = 0; $delta < $this->field['cardinality']; $delta++) {
      // The field value loaded matches the one inserted or updated.
      $this->assertEqual($entity->{$this->field_name}[$delta]->value , $values[$current_revision][$delta]['value'], format_string('Current revision: expected value %delta was found.', array('%delta' => $delta)));
      // The value added in hook_field_load() is found.
      $this->assertEqual($entity->{$this->field_name}[$delta]->additional_key, 'additional_value', format_string('Current revision: extra information for value %delta was found', array('%delta' => $delta)));
    }

    // Confirm each revision loads the correct data.
    foreach (array_keys($revision) as $revision_id) {
      $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => $revision_id));
      field_attach_load_revision($entity_type, array(0 => $entity));
      // Number of values per field loaded equals the field cardinality.
      $this->assertEqual(count($entity->{$this->field_name}), $this->field['cardinality'], format_string('Revision %revision_id: expected number of values.', array('%revision_id' => $revision_id)));
      for ($delta = 0; $delta < $this->field['cardinality']; $delta++) {
        // The field value loaded matches the one inserted or updated.
        $this->assertEqual($entity->{$this->field_name}[$delta]->value, $values[$revision_id][$delta]['value'], format_string('Revision %revision_id: expected value %delta was found.', array('%revision_id' => $revision_id, '%delta' => $delta)));
        // The value added in hook_field_load() is found.
        $this->assertEqual($entity->{$this->field_name}[$delta]->additional_key, 'additional_value', format_string('Revision %revision_id: extra information for value %delta was found', array('%revision_id' => $revision_id, '%delta' => $delta)));
      }
    }
  }

  /**
   * Test the 'multiple' load feature.
   */
  function testFieldAttachLoadMultiple() {
    $entity_type = 'entity_test';

    // Define 2 bundles.
    $bundles = array(
      1 => 'test_bundle_1',
      2 => 'test_bundle_2',
    );
    entity_test_create_bundle($bundles[1]);
    entity_test_create_bundle($bundles[2]);
    // Define 3 fields:
    // - field_1 is in bundle_1 and bundle_2,
    // - field_2 is in bundle_1,
    // - field_3 is in bundle_2.
    $field_bundles_map = array(
      1 => array(1, 2),
      2 => array(1),
      3 => array(2),
    );
    for ($i = 1; $i <= 3; $i++) {
      $field_names[$i] = 'field_' . $i;
      $field = entity_create('field_entity', array('field_name' => $field_names[$i], 'type' => 'test_field'));
      $field->save();
      $field_ids[$i] = $field['uuid'];
      foreach ($field_bundles_map[$i] as $bundle) {
        entity_create('field_instance', array(
          'field_name' => $field_names[$i],
          'entity_type' => $entity_type,
          'bundle' => $bundles[$bundle],
          'settings' => array(
            // Configure the instance so that we test hook_field_load()
            // (see field_test_field_load() in field_test.module).
            'test_hook_field_load' => TRUE,
          ),
        ))->save();
      }
    }

    // Create one test entity per bundle, with random values.
    foreach ($bundles as $index => $bundle) {
      $entities[$index] = entity_create($entity_type, array('id' => $index, 'revision_id' => $index, 'type' => $bundle));
      $entity = clone($entities[$index]);
      $instances = field_info_instances($entity_type, $bundle);
      foreach ($instances as $field_name => $instance) {
        $values[$index][$field_name] = mt_rand(1, 127);
        $entity->$field_name->setValue(array('value' => $values[$index][$field_name]));
      }
      field_attach_insert($entity);
    }

    // Check that a single load correctly loads field values for both entities.
    field_attach_load($entity_type, $entities);
    foreach ($entities as $index => $entity) {
      $instances = field_info_instances($entity_type, $bundles[$index]);
      foreach ($instances as $field_name => $instance) {
        // The field value loaded matches the one inserted.
        $this->assertEqual($entity->{$field_name}->value, $values[$index][$field_name], format_string('Entity %index: expected value was found.', array('%index' => $index)));
        // The value added in hook_field_load() is found.
        $this->assertEqual($entity->{$field_name}->additional_key, 'additional_value', format_string('Entity %index: extra information was found', array('%index' => $index)));
      }
    }

    // Check that the single-field load option works.
    $entity = entity_create($entity_type, array('id' => 1, 'revision_id' => 1, 'type' => $bundles[1]));
    $instance = field_info_instance($entity->entityType(), $field_names[1], $entity->bundle());
    field_attach_load($entity_type, array(1 => $entity), FIELD_LOAD_CURRENT, array('instance' => $instance));
    $this->assertEqual($entity->{$field_names[1]}->value, $values[1][$field_names[1]], format_string('Entity %index: expected value was found.', array('%index' => 1)));
    $this->assertEqual($entity->{$field_names[1]}->additional_key, 'additional_value', format_string('Entity %index: extra information was found', array('%index' => 1)));
    $this->assert(empty($entity->{$field_names[2]}->value), format_string('Entity %index: field %field_name is not loaded.', array('%index' => 2, '%field_name' => $field_names[2])));
    $this->assert(!isset($entity->{$field_names[3]}), format_string('Entity %index: field %field_name is not loaded.', array('%index' => 3, '%field_name' => $field_names[3])));
  }

  /**
   * Test saving and loading fields using different storage backends.
   */
  function testFieldAttachSaveLoadDifferentStorage() {
    $entity_type = 'entity_test';

    // Create two fields using different storage backends, and their instances.
    $fields = array(
      array(
        'field_name' => 'field_1',
        'type' => 'test_field',
        'cardinality' => 4,
        'storage' => array('type' => 'field_sql_storage')
      ),
      array(
        'field_name' => 'field_2',
        'type' => 'test_field',
        'cardinality' => 4,
        'storage' => array('type' => 'field_test_storage')
      ),
    );
    foreach ($fields as $field) {
      entity_create('field_entity', $field)->save();
      $instance = array(
        'field_name' => $field['field_name'],
        'entity_type' => $entity_type,
        'bundle' => $entity_type,
      );
      entity_create('field_instance', $instance)->save();
    }

    $entity_init = entity_create($entity_type, array('id' => 1, 'revision_id' => 1));

    // Create entity and insert random values.
    $entity = clone($entity_init);
    $values = array();
    foreach ($fields as $field) {
      $values[$field['field_name']] = $this->_generateTestFieldValues($this->field['cardinality']);
      $entity->{$field['field_name']} = $values[$field['field_name']];
    }
    field_attach_insert($entity);

    // Check that values are loaded as expected.
    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->id() => $entity));
    foreach ($fields as $field) {
      $this->assertEqual($values[$field['field_name']], $entity->{$field['field_name']}->getValue(), format_string('%storage storage: expected values were found.', array('%storage' => $field['storage']['type'])));
    }
  }

  /**
   * Test storage details alteration.
   *
   * @see field_test_storage_details_alter()
   */
  function testFieldStorageDetailsAlter() {
    $field_name = 'field_test_change_my_details';
    $field = entity_create('field_entity', array(
      'field_name' => $field_name,
      'type' => 'test_field',
      'cardinality' => 4,
      'storage' => array('type' => 'field_test_storage'),
    ));
    $field->save();
    $instance = entity_create('field_instance', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ));
    $instance->save();

    // The storage details are indexed by a storage engine type.
    $this->assertTrue(array_key_exists('drupal_variables', $field['storage_details']), 'The storage type is Drupal variables.');

    $details = $field['storage_details']['drupal_variables'];

    // The field_test storage details are indexed by variable name. The details
    // are altered, so moon and mars are correct for this test.
    $this->assertTrue(array_key_exists('moon', $details[FIELD_LOAD_CURRENT]), 'Moon is available in the instance array.');
    $this->assertTrue(array_key_exists('mars', $details[FIELD_LOAD_REVISION]), 'Mars is available in the instance array.');

    // Test current and revision storage details together because the columns
    // are the same.
    foreach ($field['columns'] as $column_name => $attributes) {
      $this->assertEqual($details[FIELD_LOAD_CURRENT]['moon'][$column_name], $column_name, format_string('Column name %value matches the definition in %bin.', array('%value' => $column_name, '%bin' => 'moon[FIELD_LOAD_CURRENT]')));
      $this->assertEqual($details[FIELD_LOAD_REVISION]['mars'][$column_name], $column_name, format_string('Column name %value matches the definition in %bin.', array('%value' => $column_name, '%bin' => 'mars[FIELD_LOAD_REVISION]')));
    }
  }

  /**
   * Tests insert and update with empty or NULL fields.
   */
  function testFieldAttachSaveEmptyData() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);

    $entity_init = entity_create($entity_type, array('id' => 1, 'revision_id' => 1));

    // Insert: Field is NULL.
    field_cache_clear();
    $entity = clone($entity_init);
    $entity->{$this->field_name} = NULL;
    field_attach_insert($entity);

    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->id() => $entity));
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Insert: NULL field results in no value saved');

    // Add some real data.
    field_cache_clear();
    $entity = clone($entity_init);
    $values = $this->_generateTestFieldValues(1);
    $entity->{$this->field_name} = $values;
    field_attach_insert($entity);

    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->id() => $entity));
    $this->assertEqual($entity->{$this->field_name}->getValue(), $values, 'Field data saved');

    // Update: Field is NULL. Data should be wiped.
    field_cache_clear();
    $entity = clone($entity_init);
    $entity->{$this->field_name} = NULL;
    field_attach_update($entity);

    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->id() => $entity));
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Update: NULL field removes existing values');

    // Re-add some data.
    field_cache_clear();
    $entity = clone($entity_init);
    $values = $this->_generateTestFieldValues(1);
    $entity->{$this->field_name} = $values;
    field_attach_update($entity);

    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->id() => $entity));
    $this->assertEqual($entity->{$this->field_name}->getValue(), $values, 'Field data saved');

    // Update: Field is empty array. Data should be wiped.
    field_cache_clear();
    $entity = clone($entity_init);
    $entity->{$this->field_name} = array();
    field_attach_update($entity);

    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->id() => $entity));
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Update: empty array removes existing values');
  }

  /**
   * Test insert with empty or NULL fields, with default value.
   */
  function testFieldAttachSaveEmptyDataDefaultValue() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);

    // Add a default value function.
    $this->instance['default_value_function'] = 'field_test_default_value';
    $this->instance->save();

    // Verify that fields are populated with default values.
    $entity_init = entity_create($entity_type, array('id' => 1, 'revision_id' => 1));
    $default = field_test_default_value($entity_init, $this->field, $this->instance);
    $this->assertEqual($entity_init->{$this->field_name}->getValue(), $default, 'Default field value correctly populated.');

    // Insert: Field is NULL.
    $entity = clone($entity_init);
    $entity->getBCEntity()->{$this->field_name} = NULL;
    field_attach_insert($entity);

    $entity = clone($entity_init);
    $entity->getBCEntity()->{$this->field_name} = array();
    field_attach_load($entity_type, array($entity->id() => $entity));
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Insert: NULL field results in no value saved');

    // Verify that prepopulated field values are not overwritten by defaults.
    $value = array(array('value' => $default[0]['value'] - mt_rand(1, 127)));
    $entity = entity_create($entity_type, array('type' => $entity_init->bundle(), $this->field_name => $value));
    $this->assertEqual($entity->{$this->field_name}->getValue(), $value, 'Prepopulated field value correctly maintained.');
  }

  /**
   * Test field_attach_delete().
   */
  function testFieldAttachDelete() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);
    $rev[0] = entity_create($entity_type, array('id' => 0, 'revision_id' => 0, 'type' => $this->instance['bundle']));

    // Create revision 0
    $values = $this->_generateTestFieldValues($this->field['cardinality']);
    $rev[0]->{$this->field_name} = $values;
    field_attach_insert($rev[0]);

    // Create revision 1
    $rev[1] = entity_create($entity_type, array('id' => 0, 'revision_id' => 1, 'type' => $this->instance['bundle']));
    $rev[1]->{$this->field_name} = $values;
    field_attach_update($rev[1]);

    // Create revision 2
    $rev[2] = entity_create($entity_type, array('id' => 0, 'revision_id' => 2, 'type' => $this->instance['bundle']));
    $rev[2]->{$this->field_name} = $values;
    field_attach_update($rev[2]);

    // Confirm each revision loads
    foreach (array_keys($rev) as $vid) {
      $read = entity_create($entity_type, array('id' => 0, 'revision_id' => $vid, 'type' => $this->instance['bundle']));
      field_attach_load_revision($entity_type, array(0 => $read));
      $this->assertEqual(count($read->{$this->field_name}), $this->field['cardinality'], "The test entity revision $vid has {$this->field['cardinality']} values.");
    }

    // Delete revision 1, confirm the other two still load.
    field_attach_delete_revision($rev[1]);
    foreach (array(0, 2) as $vid) {
      $read = entity_create($entity_type, array('id' => 0, 'revision_id' => $vid, 'type' => $this->instance['bundle']));
      field_attach_load_revision($entity_type, array(0 => $read));
      $this->assertEqual(count($read->{$this->field_name}), $this->field['cardinality'], "The test entity revision $vid has {$this->field['cardinality']} values.");
    }

    // Confirm the current revision still loads
    $read = entity_create($entity_type, array('id' => 0, 'revision_id' => 2, 'type' => $this->instance['bundle']));
    field_attach_load($entity_type, array(0 => $read));
    $this->assertEqual(count($read->{$this->field_name}), $this->field['cardinality'], "The test entity current revision has {$this->field['cardinality']} values.");

    // Delete all field data, confirm nothing loads
    field_attach_delete($rev[2]);
    foreach (array(0, 1, 2) as $vid) {
      $read = entity_create($entity_type, array('id' => 0, 'revision_id' => $vid, 'type' => $this->instance['bundle']));
      field_attach_load_revision($entity_type, array(0 => $read));
      $this->assertIdentical($read->{$this->field_name}[0]->getValue(), array(), "The test entity revision $vid is deleted.");
    }
    $read = entity_create($entity_type, array('id' => 0, 'revision_id' => 2, 'type' => $this->instance['bundle']));
    field_attach_load($entity_type, array(0 => $read));
    $this->assertIdentical($read->{$this->field_name}[0]->getValue(), array(), 'The test entity current revision is deleted.');
  }

  /**
   * Test entity_bundle_create() and entity_bundle_rename().
   */
  function testEntityCreateRenameBundle() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);

    // Create a new bundle.
    $new_bundle = 'test_bundle_' . drupal_strtolower($this->randomName());
    entity_test_create_bundle($new_bundle, NULL, $entity_type);

    // Add an instance to that bundle.
    $this->instance_definition['bundle'] = $new_bundle;
    entity_create('field_instance', $this->instance_definition)->save();

    // Save an entity with data in the field.
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0, 'type' => $this->instance['bundle']));
    $values = $this->_generateTestFieldValues($this->field['cardinality']);
    $entity->{$this->field_name} = $values;
    field_attach_insert($entity);

    // Verify the field data is present on load.
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0, 'type' => $this->instance['bundle']));
    field_attach_load($entity_type, array(0 => $entity));
    $this->assertEqual(count($entity->{$this->field_name}), $this->field['cardinality'], "Data is retrieved for the new bundle");

    // Rename the bundle.
    $new_bundle = 'test_bundle_' . drupal_strtolower($this->randomName());
    entity_test_rename_bundle($this->instance_definition['bundle'], $new_bundle, $entity_type);

    // Check that the instance definition has been updated.
    $this->instance = field_info_instance($entity_type, $this->field_name, $new_bundle);
    $this->assertIdentical($this->instance['bundle'], $new_bundle, "Bundle name has been updated in the instance.");

    // Verify the field data is present on load.
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0, 'type' => $this->instance['bundle']));
    field_attach_load($entity_type, array(0 => $entity));
    $this->assertEqual(count($entity->{$this->field_name}), $this->field['cardinality'], "Bundle name has been updated in the field storage");
  }

  /**
   * Test entity_bundle_delete().
   */
  function testEntityDeleteBundle() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);

    // Create a new bundle.
    $new_bundle = 'test_bundle_' . drupal_strtolower($this->randomName());
    entity_test_create_bundle($new_bundle, NULL, $entity_type);

    // Add an instance to that bundle.
    $this->instance_definition['bundle'] = $new_bundle;
    entity_create('field_instance', $this->instance_definition)->save();

    // Create a second field for the test bundle
    $field_name = drupal_strtolower($this->randomName() . '_field_name');
    $field = array('field_name' => $field_name, 'type' => 'test_field', 'cardinality' => 1);
    entity_create('field_entity', $field)->save();
    $instance = array(
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $this->instance['bundle'],
      'label' => $this->randomName() . '_label',
      'description' => $this->randomName() . '_description',
      'weight' => mt_rand(0, 127),
    );
    entity_create('field_instance', $instance)->save();

    // Save an entity with data for both fields
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0, 'type' => $this->instance['bundle']));
    $values = $this->_generateTestFieldValues($this->field['cardinality']);
    $entity->{$this->field_name} = $values;
    $entity->{$field_name} = $this->_generateTestFieldValues(1);
    field_attach_insert($entity);

    // Verify the fields are present on load
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0, 'type' => $this->instance['bundle']));
    field_attach_load($entity_type, array(0 => $entity));
    $this->assertEqual(count($entity->{$this->field_name}), 4, 'First field got loaded');
    $this->assertEqual(count($entity->{$field_name}), 1, 'Second field got loaded');

    // Delete the bundle.
    entity_test_delete_bundle($this->instance['bundle'], $entity_type);

    // Verify no data gets loaded
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0, 'type' => $this->instance['bundle']));
    field_attach_load($entity_type, array(0 => $entity));
    $this->assertTrue(empty($entity->{$this->field_name}), 'No data for first field');
    $this->assertTrue(empty($entity->{$field_name}), 'No data for second field');

    // Verify that the instances are gone
    $this->assertFalse(field_read_instance('entity_test', $this->field_name, $this->instance['bundle']), "First field is deleted");
    $this->assertFalse(field_read_instance('entity_test', $field_name, $instance['bundle']), "Second field is deleted");
  }

}
