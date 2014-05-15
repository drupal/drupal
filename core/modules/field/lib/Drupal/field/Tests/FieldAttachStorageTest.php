<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldAttachStorageTest.
 */

namespace Drupal\field\Tests;
use Drupal\field\Entity\FieldInstanceConfig;

/**
 * Unit test class for storage-related field behavior.
 */
class FieldAttachStorageTest extends FieldUnitTestBase {

  /**
   * The field instance.
   *
   * @var \Drupal\field\Entity\FieldInstanceConfig
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
    $cardinality = $this->field->getCardinality();

    // Configure the instance so that we test
    // \Drupal\field_test\Plugin\Field\FieldType\TestItem::getCacheData().
    $this->instance->settings['test_cached_data'] = TRUE;
    $this->instance->save();

    // TODO : test empty values filtering and "compression" (store consecutive deltas).
    // Preparation: create three revisions and store them in $revision array.
    $values = array();
    $entity = entity_create($entity_type);
    for ($revision_id = 0; $revision_id < 3; $revision_id++) {
      // Note: we try to insert one extra value.
      $current_values = $this->_generateTestFieldValues($cardinality + 1);
      $entity->{$this->field_name}->setValue($current_values);
      $entity->setNewRevision();
      $entity->save();
      $entity_id = $entity->id();
      $current_revision = $entity->getRevisionId();
      $values[$current_revision] = $current_values;
    }

    $storage =  $this->container->get('entity.manager')->getStorage($entity_type);
    $storage->resetCache();
    $entity = $storage->load($entity_id);
    // Confirm current revision loads the correct data.
    // Number of values per field loaded equals the field cardinality.
    $this->assertEqual(count($entity->{$this->field_name}), $cardinality, 'Current revision: expected number of values');
    for ($delta = 0; $delta < $cardinality; $delta++) {
      // The field value loaded matches the one inserted or updated.
      $this->assertEqual($entity->{$this->field_name}[$delta]->value , $values[$current_revision][$delta]['value'], format_string('Current revision: expected value %delta was found.', array('%delta' => $delta)));
      // The value added in
      // \Drupal\field_test\Plugin\Field\FieldType\TestItem::getCacheData() is
      // found.
      $this->assertEqual($entity->{$this->field_name}[$delta]->additional_key, 'additional_value', format_string('Current revision: extra information for value %delta was found', array('%delta' => $delta)));
    }

    // Confirm each revision loads the correct data.
    foreach (array_keys($values) as $revision_id) {
      $entity = $storage->loadRevision($revision_id);
      // Number of values per field loaded equals the field cardinality.
      $this->assertEqual(count($entity->{$this->field_name}), $cardinality, format_string('Revision %revision_id: expected number of values.', array('%revision_id' => $revision_id)));
      for ($delta = 0; $delta < $cardinality; $delta++) {
        // The field value loaded matches the one inserted or updated.
        $this->assertEqual($entity->{$this->field_name}[$delta]->value, $values[$revision_id][$delta]['value'], format_string('Revision %revision_id: expected value %delta was found.', array('%revision_id' => $revision_id, '%delta' => $delta)));
      }
    }
  }

  /**
   * Test the 'multiple' load feature.
   */
  function testFieldAttachLoadMultiple() {
    $entity_type = 'entity_test_rev';

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
      $field = entity_create('field_config', array(
        'name' => $field_names[$i],
        'entity_type' => $entity_type,
        'type' => 'test_field',
      ));
      $field->save();
      $field_ids[$i] = $field->uuid();
      foreach ($field_bundles_map[$i] as $bundle) {
        entity_create('field_instance_config', array(
          'field_name' => $field_names[$i],
          'entity_type' => $entity_type,
          'bundle' => $bundles[$bundle],
          // Configure the instance so that we test
          // \Drupal\field_test\Plugin\Field\FieldType\TestItem::getCacheData().
          'settings' => array(
            'test_cached_data' => TRUE,
          ),
        ))->save();
      }
    }

    // Create one test entity per bundle, with random values.
    foreach ($bundles as $index => $bundle) {
      $entities[$index] = entity_create($entity_type, array('id' => $index, 'revision_id' => $index, 'type' => $bundle));
      $entity = clone($entities[$index]);
      foreach ($field_names as $field_name) {
        if (!$entity->hasField($field_name)) {
          continue;
        }
        $values[$index][$field_name] = mt_rand(1, 127);
        $entity->$field_name->setValue(array('value' => $values[$index][$field_name]));
      }
      $entity->enforceIsnew();
      $entity->save();
    }

    // Check that a single load correctly loads field values for both entities.
    $controller = \Drupal::entityManager()->getStorage($entity->getEntityTypeId());
    $controller->resetCache();
    $entities = $controller->loadMultiple();
    foreach ($entities as $index => $entity) {
      foreach ($field_names as $field_name) {
        if (!$entity->hasField($field_name)) {
          continue;
        }
        // The field value loaded matches the one inserted.
        $this->assertEqual($entity->{$field_name}->value, $values[$index][$field_name], format_string('Entity %index: expected value was found.', array('%index' => $index)));
        // The value added in hook_field_load() is found.
        $this->assertEqual($entity->{$field_name}->additional_key, 'additional_value', format_string('Entity %index: extra information was found', array('%index' => $index)));
      }
    }
  }

  /**
   * Tests insert and update with empty or NULL fields.
   */
  function testFieldAttachSaveEmptyData() {
    $entity_type = 'entity_test';
    $this->createFieldWithInstance('', $entity_type);

    $entity_init = entity_create($entity_type, array('id' => 1));

    // Insert: Field is NULL.
    $entity = clone $entity_init;
    $entity->{$this->field_name} = NULL;
    $entity->enforceIsNew();
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Insert: NULL field results in no value saved');

    // All saves after this point should be updates, not inserts.
    $entity_init->enforceIsNew(FALSE);

    // Add some real data.
    $entity = clone($entity_init);
    $values = $this->_generateTestFieldValues(1);
    $entity->{$this->field_name} = $values;
    $entity = $this->entitySaveReload($entity);
    $this->assertEqual($entity->{$this->field_name}->getValue(), $values, 'Field data saved');

    // Update: Field is NULL. Data should be wiped.
    $entity = clone($entity_init);
    $entity->{$this->field_name} = NULL;
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Update: NULL field removes existing values');

    // Re-add some data.
    $entity = clone($entity_init);
    $values = $this->_generateTestFieldValues(1);
    $entity->{$this->field_name} = $values;
    $entity = $this->entitySaveReload($entity);
    $this->assertEqual($entity->{$this->field_name}->getValue(), $values, 'Field data saved');

    // Update: Field is empty array. Data should be wiped.
    $entity = clone($entity_init);
    $entity->{$this->field_name} = array();
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Update: empty array removes existing values');
  }

  /**
   * Test insert with empty or NULL fields, with default value.
   */
  function testFieldAttachSaveEmptyDataDefaultValue() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);

    // Add a default value function.
    $this->instance->default_value_function = 'field_test_default_value';
    $this->instance->save();

    // Verify that fields are populated with default values.
    $entity_init = entity_create($entity_type, array('id' => 1, 'revision_id' => 1));
    $default = field_test_default_value($entity_init, $this->field, $this->instance);
    $this->assertEqual($entity_init->{$this->field_name}->getValue(), $default, 'Default field value correctly populated.');

    // Insert: Field is NULL.
    $entity = clone($entity_init);
    $entity->{$this->field_name} = NULL;
    $entity->enforceIsNew();
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'Insert: NULL field results in no value saved');

    // Verify that prepopulated field values are not overwritten by defaults.
    $value = array(array('value' => $default[0]['value'] - mt_rand(1, 127)));
    $entity = entity_create($entity_type, array('type' => $entity_init->bundle(), $this->field_name => $value));
    $this->assertEqual($entity->{$this->field_name}->getValue(), $value, 'Prepopulated field value correctly maintained.');
  }

  /**
   * Test entity deletion.
   */
  function testFieldAttachDelete() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);
    $cardinality = $this->field->getCardinality();
    $entity = entity_create($entity_type, array('type' => $this->instance->bundle));
    $vids = array();

    // Create revision 0
    $values = $this->_generateTestFieldValues($cardinality);
    $entity->{$this->field_name} = $values;
    $entity->save();
    $vids[] = $entity->getRevisionId();

    // Create revision 1
    $entity->setNewRevision();
    $entity->save();
    $vids[] = $entity->getRevisionId();

    // Create revision 2
    $entity->setNewRevision();
    $entity->save();
    $vids[] = $entity->getRevisionId();
    $controller = $this->container->get('entity.manager')->getStorage($entity->getEntityTypeId());
    $controller->resetCache();

    // Confirm each revision loads
    foreach ($vids as $vid) {
      $revision = $controller->loadRevision($vid);
      $this->assertEqual(count($revision->{$this->field_name}), $cardinality, "The test entity revision $vid has $cardinality values.");
    }

    // Delete revision 1, confirm the other two still load.
    $controller->deleteRevision($vids[1]);
    $controller->resetCache();
    foreach (array(0, 2) as $key) {
      $vid = $vids[$key];
      $revision = $controller->loadRevision($vid);
      $this->assertEqual(count($revision->{$this->field_name}), $cardinality, "The test entity revision $vid has $cardinality values.");
    }

    // Confirm the current revision still loads
    $controller->resetCache();
    $current = $controller->load($entity->id());
    $this->assertEqual(count($current->{$this->field_name}), $cardinality, "The test entity current revision has $cardinality values.");

    // Delete all field data, confirm nothing loads
    $entity->delete();
    $controller->resetCache();
    foreach (array(0, 1, 2) as $vid) {
      $revision = $controller->loadRevision($vid);
      $this->assertFalse($revision);
    }
    $this->assertFalse($controller->load($entity->id()));
  }

  /**
   * Test entity_bundle_create() and entity_bundle_rename().
   */
  function testEntityCreateRenameBundle() {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithInstance('', $entity_type);
    $cardinality = $this->field->getCardinality();

    // Create a new bundle.
    $new_bundle = 'test_bundle_' . drupal_strtolower($this->randomName());
    entity_test_create_bundle($new_bundle, NULL, $entity_type);

    // Add an instance to that bundle.
    $this->instance_definition['bundle'] = $new_bundle;
    entity_create('field_instance_config', $this->instance_definition)->save();

    // Save an entity with data in the field.
    $entity = entity_create($entity_type, array('type' => $this->instance->bundle));
    $values = $this->_generateTestFieldValues($cardinality);
    $entity->{$this->field_name} = $values;

    // Verify the field data is present on load.
    $entity = $this->entitySaveReload($entity);
    $this->assertEqual(count($entity->{$this->field_name}), $cardinality, "Data is retrieved for the new bundle");

    // Rename the bundle.
    $new_bundle = 'test_bundle_' . drupal_strtolower($this->randomName());
    entity_test_rename_bundle($this->instance_definition['bundle'], $new_bundle, $entity_type);

    // Check that the instance definition has been updated.
    $this->instance = FieldInstanceConfig::loadByName($entity_type, $new_bundle, $this->field_name);
    $this->assertIdentical($this->instance->bundle, $new_bundle, "Bundle name has been updated in the instance.");

    // Verify the field data is present on load.
    $controller = $this->container->get('entity.manager')->getStorage($entity->getEntityTypeId());
    $controller->resetCache();
    $entity = $controller->load($entity->id());
    $this->assertEqual(count($entity->{$this->field_name}), $cardinality, "Bundle name has been updated in the field storage");
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
    entity_create('field_instance_config', $this->instance_definition)->save();

    // Create a second field for the test bundle
    $field_name = drupal_strtolower($this->randomName() . '_field_name');
    $field = array(
      'name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'test_field',
      'cardinality' => 1,
    );
    entity_create('field_config', $field)->save();
    $instance = array(
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $this->instance->bundle,
      'label' => $this->randomName() . '_label',
      'description' => $this->randomName() . '_description',
      'weight' => mt_rand(0, 127),
    );
    entity_create('field_instance_config', $instance)->save();

    // Save an entity with data for both fields
    $entity = entity_create($entity_type, array('type' => $this->instance->bundle));
    $values = $this->_generateTestFieldValues($this->field->getCardinality());
    $entity->{$this->field_name} = $values;
    $entity->{$field_name} = $this->_generateTestFieldValues(1);
    $entity = $this->entitySaveReload($entity);

    // Verify the fields are present on load
    $this->assertEqual(count($entity->{$this->field_name}), 4, 'First field got loaded');
    $this->assertEqual(count($entity->{$field_name}), 1, 'Second field got loaded');

    // Delete the bundle.
    entity_test_delete_bundle($this->instance->bundle, $entity_type);

    // Verify no data gets loaded
    $controller = $this->container->get('entity.manager')->getStorage($entity->getEntityTypeId());
    $controller->resetCache();
    $entity= $controller->load($entity->id());

    $this->assertTrue(empty($entity->{$this->field_name}), 'No data for first field');
    $this->assertTrue(empty($entity->{$field_name}), 'No data for second field');

    // Verify that the instances are gone.
    $this->assertFalse(entity_load('field_instance_config', 'entity_test.' . $this->instance->bundle . '.' . $this->field_name), "First field is deleted");
    $this->assertFalse(entity_load('field_instance_config', 'entity_test.' . $instance['bundle']. '.' . $field_name), "Second field is deleted");
  }

}
