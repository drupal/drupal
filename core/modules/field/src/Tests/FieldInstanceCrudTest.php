<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldInstanceCrudTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\FieldException;

/**
 * Create field entities by attaching fields to entities.
 *
 * @group field
 */
class FieldInstanceCrudTest extends FieldUnitTestBase {

  /**
   * The field storage entity.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field entity definition.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The field instance entity definition.
   *
   * @var array
   */
  protected $instanceDefinition;

  function setUp() {
    parent::setUp();

    $this->fieldStorageDefinition = array(
      'name' => drupal_strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    $this->fieldStorage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $this->fieldStorage->save();
    $this->instanceDefinition = array(
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
  }

  // TODO : test creation with
  // - a full fledged $instance structure, check that all the values are there
  // - a minimal $instance structure, check all default values are set
  // defer actual $instance comparison to a helper function, used for the two cases above,
  // and for testUpdateFieldInstance

  /**
   * Test the creation of a field instance.
   */
  function testCreateFieldInstance() {
    $instance = entity_create('field_instance_config', $this->instanceDefinition);
    $instance->save();

    // Read the configuration. Check against raw configuration data rather than
    // the loaded ConfigEntity, to be sure we check that the defaults are
    // applied on write.
    $config = \Drupal::config('field.instance.' . $instance->id())->get();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    // Check that default values are set.
    $this->assertEqual($config['required'], FALSE, 'Required defaults to false.');
    $this->assertIdentical($config['label'], $this->instanceDefinition['field_name'], 'Label defaults to field name.');
    $this->assertIdentical($config['description'], '', 'Description defaults to empty string.');

    // Check that default settings are set.
    $this->assertEqual($config['settings'], $field_type_manager->getDefaultInstanceSettings($this->fieldStorageDefinition['type']) , 'Default instance settings have been written.');

    // Check that the denormalized 'field_type' was properly written.
    $this->assertEqual($config['field_type'], $this->fieldStorageDefinition['type']);

    // Guarantee that the field/bundle combination is unique.
    try {
      entity_create('field_instance_config', $this->instanceDefinition)->save();
      $this->fail(t('Cannot create two instances with the same field / bundle combination.'));
    }
    catch (EntityStorageException $e) {
      $this->pass(t('Cannot create two instances with the same field / bundle combination.'));
    }

    // Check that the specified field exists.
    try {
      $this->instanceDefinition['field_name'] = $this->randomMachineName();
      entity_create('field_instance_config', $this->instanceDefinition)->save();
      $this->fail(t('Cannot create an instance of a non-existing field.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create an instance of a non-existing field.'));
    }

    // TODO: test other failures.
  }

  /**
   * Test reading back an instance definition.
   */
  function testReadFieldInstance() {
    entity_create('field_instance_config', $this->instanceDefinition)->save();

    // Read the instance back.
    $instance = entity_load('field_instance_config', 'entity_test.' . $this->instanceDefinition['bundle'] . '.' . $this->instanceDefinition['field_name']);
    $this->assertTrue($this->instanceDefinition['field_name'] == $instance->getName(), 'The field was properly read.');
    $this->assertTrue($this->instanceDefinition['entity_type'] == $instance->entity_type, 'The field was properly read.');
    $this->assertTrue($this->instanceDefinition['bundle'] == $instance->bundle, 'The field was properly read.');
  }

  /**
   * Test the update of a field instance.
   */
  function testUpdateFieldInstance() {
    entity_create('field_instance_config', $this->instanceDefinition)->save();

    // Check that basic changes are saved.
    $instance = entity_load('field_instance_config', 'entity_test.' . $this->instanceDefinition['bundle'] . '.' . $this->instanceDefinition['field_name']);
    $instance->required = !$instance->isRequired();
    $instance->label = $this->randomMachineName();
    $instance->description = $this->randomMachineName();
    $instance->settings['test_instance_setting'] = $this->randomMachineName();
    $instance->save();

    $instance_new = entity_load('field_instance_config', 'entity_test.' . $this->instanceDefinition['bundle'] . '.' . $this->instanceDefinition['field_name']);
    $this->assertEqual($instance->isRequired(), $instance_new->isRequired(), '"required" change is saved');
    $this->assertEqual($instance->getLabel(), $instance_new->getLabel(), '"label" change is saved');
    $this->assertEqual($instance->getDescription(), $instance_new->getDescription(), '"description" change is saved');

    // TODO: test failures.
  }

  /**
   * Test the deletion of a field instance.
   */
  function testDeleteFieldInstance() {
    // TODO: Test deletion of the data stored in the field also.
    // Need to check that data for a 'deleted' field / instance doesn't get loaded
    // Need to check data marked deleted is cleaned on cron (not implemented yet...)

    // Create two instances for the same field so we can test that only one
    // is deleted.
    entity_create('field_instance_config', $this->instanceDefinition)->save();
    $another_instance_definition = $this->instanceDefinition;
    $another_instance_definition['bundle'] .= '_another_bundle';
    entity_test_create_bundle($another_instance_definition['bundle']);
    entity_create('field_instance_config', $another_instance_definition)->save();

    // Test that the first instance is not deleted, and then delete it.
    $instance = current(entity_load_multiple_by_properties('field_instance_config', array('entity_type' => 'entity_test', 'field_name' => $this->instanceDefinition['field_name'], 'bundle' => $this->instanceDefinition['bundle'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($instance) && empty($instance->deleted), 'A new field instance is not marked for deletion.');
    $instance->delete();

    // Make sure the instance is marked as deleted when the instance is
    // specifically loaded.
    $instance = current(entity_load_multiple_by_properties('field_instance_config', array('entity_type' => 'entity_test', 'field_name' => $this->instanceDefinition['field_name'], 'bundle' => $this->instanceDefinition['bundle'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($instance->deleted), 'A deleted field instance is marked for deletion.');

    // Try to load the instance normally and make sure it does not show up.
    $instance = entity_load('field_instance_config', 'entity_test.' . '.' . $this->instanceDefinition['bundle'] . '.' . $this->instanceDefinition['field_name']);
    $this->assertTrue(empty($instance), 'A deleted field instance is not loaded by default.');

    // Make sure the other field instance is not deleted.
    $another_instance = entity_load('field_instance_config', 'entity_test.' . $another_instance_definition['bundle'] . '.' . $another_instance_definition['field_name']);
    $this->assertTrue(!empty($another_instance) && empty($another_instance->deleted), 'A non-deleted field instance is not marked for deletion.');
  }

  /**
   * Tests the cross deletion behavior between fields and instances.
   */
  function testDeleteFieldInstanceCrossDeletion() {
    $instance_definition_2 = $this->instanceDefinition;
    $instance_definition_2['bundle'] .= '_another_bundle';
    entity_test_create_bundle($instance_definition_2['bundle']);

    // Check that deletion of a field deletes its instances.
    $field_storage = $this->fieldStorage;
    entity_create('field_instance_config', $this->instanceDefinition)->save();
    entity_create('field_instance_config', $instance_definition_2)->save();
    $field_storage->delete();
    $this->assertFalse(FieldInstanceConfig::loadByName('entity_test', $this->instanceDefinition['bundle'], $field_storage->name));
    $this->assertFalse(FieldInstanceConfig::loadByName('entity_test', $instance_definition_2['bundle'], $field_storage->name));

    // Chack that deletion of the last instance deletes the field.
    $field_storage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $field_storage->save();
    $instance = entity_create('field_instance_config', $this->instanceDefinition);
    $instance->save();
    $instance_2 = entity_create('field_instance_config', $instance_definition_2);
    $instance_2->save();
    $instance->delete();
    $this->assertTrue(FieldStorageConfig::loadByName('entity_test', $field_storage->name));
    $instance_2->delete();
    $this->assertFalse(FieldStorageConfig::loadByName('entity_test', $field_storage->name));

    // Check that deletion of all instances of the same field simultaneously
    // deletes the field.
    $field_storage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $field_storage->save();
    $instance = entity_create('field_instance_config', $this->instanceDefinition);
    $instance->save();
    $instance_2 = entity_create('field_instance_config', $instance_definition_2);
    $instance_2->save();
    $this->container->get('entity.manager')->getStorage('field_instance_config')->delete(array($instance, $instance_2));
    $this->assertFalse(FieldStorageConfig::loadByName('entity_test', $field_storage->name));
  }

}
