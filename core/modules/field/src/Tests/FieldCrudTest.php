<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldCrudTest.
 */

namespace Drupal\field\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Create field entities by attaching fields to entities.
 *
 * @group field
 */
class FieldCrudTest extends FieldUnitTestBase {

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
   * The field entity definition.
   *
   * @var array
   */
  protected $fieldDefinition;

  function setUp() {
    parent::setUp();

    $this->fieldStorageDefinition = array(
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    $this->fieldStorage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $this->fieldStorage->save();
    $this->fieldDefinition = array(
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
  }

  // TODO : test creation with
  // - a full fledged $field structure, check that all the values are there
  // - a minimal $field structure, check all default values are set
  // defer actual $field comparison to a helper function, used for the two cases above,
  // and for testUpdateField

  /**
   * Test the creation of a field.
   */
  function testCreateField() {
    // Set a state flag so that field_test.module knows to add an in-memory
    // constraint for this field.
    \Drupal::state()->set('field_test_add_constraint', $this->fieldStorage->getName());
    /** @var \Drupal\Core\Field\FieldConfigInterface $field */
    $field = entity_create('field_config', $this->fieldDefinition);
    $field->save();

    // Read the configuration. Check against raw configuration data rather than
    // the loaded ConfigEntity, to be sure we check that the defaults are
    // applied on write.
    $config = $this->config('field.field.' . $field->id())->get();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    // Check that default values are set.
    $this->assertEqual($config['required'], FALSE, 'Required defaults to false.');
    $this->assertIdentical($config['label'], $this->fieldDefinition['field_name'], 'Label defaults to field name.');
    $this->assertIdentical($config['description'], '', 'Description defaults to empty string.');

    // Check that default settings are set.
    $this->assertEqual($config['settings'], $field_type_manager->getDefaultFieldSettings($this->fieldStorageDefinition['type']) , 'Default field settings have been written.');

    // Check that the denormalized 'field_type' was properly written.
    $this->assertEqual($config['field_type'], $this->fieldStorageDefinition['type']);

    // Test constraints are applied. A Range constraint is added dynamically to
    // limit the field to values between 0 and 32.
    // @see field_test_entity_bundle_field_info_alter()
    $this->doFieldValidationTests();

    // Test FieldConfigBase::setPropertyConstraints().
    \Drupal::state()->set('field_test_set_constraint', $this->fieldStorage->getName());
    \Drupal::state()->set('field_test_add_constraint', FALSE);
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $this->doFieldValidationTests();

    // Guarantee that the field/bundle combination is unique.
    try {
      entity_create('field_config', $this->fieldDefinition)->save();
      $this->fail(t('Cannot create two fields with the same field / bundle combination.'));
    }
    catch (EntityStorageException $e) {
      $this->pass(t('Cannot create two fields with the same field / bundle combination.'));
    }

    // Check that the specified field exists.
    try {
      $this->fieldDefinition['field_name'] = $this->randomMachineName();
      entity_create('field_config', $this->fieldDefinition)->save();
      $this->fail(t('Cannot create a field with a non-existing storage.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a non-existing storage.'));
    }

    // TODO: test other failures.
  }

  /**
   * Test reading back a field definition.
   */
  function testReadField() {
    entity_create('field_config', $this->fieldDefinition)->save();

    // Read the field back.
    $field = FieldConfig::load('entity_test.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $this->assertTrue($this->fieldDefinition['field_name'] == $field->getName(), 'The field was properly read.');
    $this->assertTrue($this->fieldDefinition['entity_type'] == $field->entity_type, 'The field was properly read.');
    $this->assertTrue($this->fieldDefinition['bundle'] == $field->bundle, 'The field was properly read.');
  }

  /**
   * Test the update of a field.
   */
  function testUpdateField() {
    entity_create('field_config', $this->fieldDefinition)->save();

    // Check that basic changes are saved.
    $field = FieldConfig::load('entity_test.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $field->required = !$field->isRequired();
    $field->label = $this->randomMachineName();
    $field->description = $this->randomMachineName();
    $field->settings['test_field_setting'] = $this->randomMachineName();
    $field->save();

    $field_new = FieldConfig::load('entity_test.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $this->assertEqual($field->isRequired(), $field_new->isRequired(), '"required" change is saved');
    $this->assertEqual($field->getLabel(), $field_new->getLabel(), '"label" change is saved');
    $this->assertEqual($field->getDescription(), $field_new->getDescription(), '"description" change is saved');

    // TODO: test failures.
  }

  /**
   * Test the deletion of a field.
   */
  function testDeleteField() {
    // TODO: Test deletion of the data stored in the field also.
    // Need to check that data for a 'deleted' field / storage doesn't get loaded
    // Need to check data marked deleted is cleaned on cron (not implemented yet...)

    // Create two fields for the same field storage so we can test that only one
    // is deleted.
    entity_create('field_config', $this->fieldDefinition)->save();
    $another_field_definition = $this->fieldDefinition;
    $another_field_definition['bundle'] .= '_another_bundle';
    entity_test_create_bundle($another_field_definition['bundle']);
    entity_create('field_config', $another_field_definition)->save();

    // Test that the first field is not deleted, and then delete it.
    $field = current(entity_load_multiple_by_properties('field_config', array('entity_type' => 'entity_test', 'field_name' => $this->fieldDefinition['field_name'], 'bundle' => $this->fieldDefinition['bundle'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($field) && empty($field->deleted), 'A new field is not marked for deletion.');
    $field->delete();

    // Make sure the field is marked as deleted when it is specifically loaded.
    $field = current(entity_load_multiple_by_properties('field_config', array('entity_type' => 'entity_test', 'field_name' => $this->fieldDefinition['field_name'], 'bundle' => $this->fieldDefinition['bundle'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($field->deleted), 'A deleted field is marked for deletion.');

    // Try to load the field normally and make sure it does not show up.
    $field = FieldConfig::load('entity_test.' . '.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $this->assertTrue(empty($field), 'A deleted field is not loaded by default.');

    // Make sure the other field is not deleted.
    $another_field = FieldConfig::load('entity_test.' . $another_field_definition['bundle'] . '.' . $another_field_definition['field_name']);
    $this->assertTrue(!empty($another_field) && empty($another_field->deleted), 'A non-deleted field is not marked for deletion.');
  }

  /**
   * Tests the cross deletion behavior between field storages and fields.
   */
  function testDeleteFieldCrossDeletion() {
    $field_definition_2 = $this->fieldDefinition;
    $field_definition_2['bundle'] .= '_another_bundle';
    entity_test_create_bundle($field_definition_2['bundle']);

    // Check that deletion of a field storage deletes its fields.
    $field_storage = $this->fieldStorage;
    entity_create('field_config', $this->fieldDefinition)->save();
    entity_create('field_config', $field_definition_2)->save();
    $field_storage->delete();
    $this->assertFalse(FieldConfig::loadByName('entity_test', $this->fieldDefinition['bundle'], $field_storage->field_name));
    $this->assertFalse(FieldConfig::loadByName('entity_test', $field_definition_2['bundle'], $field_storage->field_name));

    // Chack that deletion of the last field deletes the storage.
    $field_storage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $field_storage->save();
    $field = entity_create('field_config', $this->fieldDefinition);
    $field->save();
    $field_2 = entity_create('field_config', $field_definition_2);
    $field_2->save();
    $field->delete();
    $this->assertTrue(FieldStorageConfig::loadByName('entity_test', $field_storage->field_name));
    $field_2->delete();
    $this->assertFalse(FieldStorageConfig::loadByName('entity_test', $field_storage->field_name));

    // Check that deletion of all fields using a storage simultaneously deletes
    // the storage.
    $field_storage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $field_storage->save();
    $field = entity_create('field_config', $this->fieldDefinition);
    $field->save();
    $field_2 = entity_create('field_config', $field_definition_2);
    $field_2->save();
    $this->container->get('entity.manager')->getStorage('field_config')->delete(array($field, $field_2));
    $this->assertFalse(FieldStorageConfig::loadByName('entity_test', $field_storage->field_name));
  }

  /**
   * Tests configurable field validation.
   *
   * @see field_test_entity_bundle_field_info_alter()
   */
  protected function doFieldValidationTests() {
    $entity = entity_create('entity_test');
    $entity->set($this->fieldStorage->getName(), 1);
    $violations = $entity->validate();
    $this->assertEqual(count($violations), 0, 'No violations found when in-range value passed.');

    $entity->set($this->fieldStorage->getName(), 33);
    $violations = $entity->validate();
    $this->assertEqual(count($violations), 1, 'Violations found when using value outside the range.');
    $this->assertEqual($violations[0]->getPropertyPath(), $this->fieldStorage->getName() . '.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('This value should be %limit or less.', [
      '%limit' => 32,
    ]));
  }

}
