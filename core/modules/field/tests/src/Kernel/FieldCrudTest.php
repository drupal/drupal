<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Create field entities by attaching fields to entities.
 *
 * @coversDefaultClass \Drupal\Core\Field\FieldConfigBase
 *
 * @group field
 */
class FieldCrudTest extends FieldKernelTestBase {

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

  public function setUp() {
    parent::setUp();

    $this->fieldStorageDefinition = [
      'field_name' => mb_strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();
    $this->fieldDefinition = [
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
  }

  // TODO : test creation with
  // - a full fledged $field structure, check that all the values are there
  // - a minimal $field structure, check all default values are set
  // defer actual $field comparison to a helper function, used for the two cases above,
  // and for testUpdateField

  /**
   * Test the creation of a field.
   */
  public function testCreateField() {
    $field = FieldConfig::create($this->fieldDefinition);
    $field->save();

    $field = FieldConfig::load($field->id());
    $this->assertEquals('TRUE', $field->getSetting('field_setting_from_config_data'));
    $this->assertNull($field->getSetting('config_data_from_field_setting'));

    // Read the configuration. Check against raw configuration data rather than
    // the loaded ConfigEntity, to be sure we check that the defaults are
    // applied on write.
    $config = $this->config('field.field.' . $field->id())->get();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    $this->assertTrue($config['settings']['config_data_from_field_setting']);
    $this->assertTrue(!isset($config['settings']['field_setting_from_config_data']));

    // Since we are working with raw configuration, this needs to be unset
    // manually.
    // @see Drupal\field_test\Plugin\Field\FieldType\TestItem::fieldSettingsFromConfigData()
    unset($config['settings']['config_data_from_field_setting']);

    // Check that default values are set.
    $this->assertEqual($config['required'], FALSE, 'Required defaults to false.');
    $this->assertIdentical($config['label'], $this->fieldDefinition['field_name'], 'Label defaults to field name.');
    $this->assertIdentical($config['description'], '', 'Description defaults to empty string.');

    // Check that default settings are set.
    $this->assertEqual($config['settings'], $field_type_manager->getDefaultFieldSettings($this->fieldStorageDefinition['type']), 'Default field settings have been written.');

    // Check that the denormalized 'field_type' was properly written.
    $this->assertEqual($config['field_type'], $this->fieldStorageDefinition['type']);

    // Guarantee that the field/bundle combination is unique.
    try {
      FieldConfig::create($this->fieldDefinition)->save();
      $this->fail('Cannot create two fields with the same field / bundle combination.');
    }
    catch (EntityStorageException $e) {
      // Expected exception; just continue testing.
    }

    // Check that the specified field exists.
    try {
      $this->fieldDefinition['field_name'] = $this->randomMachineName();
      FieldConfig::create($this->fieldDefinition)->save();
      $this->fail('Cannot create a field with a non-existing storage.');
    }
    catch (FieldException $e) {
      // Expected exception; just continue testing.
    }

    // TODO: test other failures.
  }

  /**
   * Tests setting and adding property constraints to a configurable field.
   *
   * @covers ::setPropertyConstraints
   * @covers ::addPropertyConstraints
   */
  public function testFieldPropertyConstraints() {
    $field = FieldConfig::create($this->fieldDefinition);
    $field->save();
    $field_name = $this->fieldStorage->getName();

    // Test that constraints are applied to configurable fields. A TestField and
    // a Range constraint are added dynamically to limit the field to values
    // between 0 and 32.
    // @see field_test_entity_bundle_field_info_alter()
    \Drupal::state()->set('field_test_constraint', $field_name);

    // Clear the field definitions cache so the new constraints added by
    // field_test_entity_bundle_field_info_alter() are taken into consideration.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Test the newly added property constraints in the same request as when the
    // caches were cleared. This will test the field definitions that are stored
    // in the static cache of
    // \Drupal\Core\Entity\EntityFieldManager::getFieldDefinitions().
    $this->doFieldPropertyConstraintsTests();

    // In order to test a real-world scenario where the property constraints are
    // only stored in the persistent cache of
    // \Drupal\Core\Entity\EntityFieldManager::getFieldDefinitions(), we need to
    // simulate a new request by removing the 'entity_field.manager' service,
    // thus forcing it to be re-initialized without static caches.
    \Drupal::getContainer()->set('entity_field.manager', NULL);

    // This will test the field definitions that are stored in the persistent
    // cache by \Drupal\Core\Entity\EntityFieldManager::getFieldDefinitions().
    $this->doFieldPropertyConstraintsTests();
  }

  /**
   * Tests configurable field validation.
   *
   * @see field_test_entity_bundle_field_info_alter()
   */
  protected function doFieldPropertyConstraintsTests() {
    $field_name = $this->fieldStorage->getName();

    // Check that a valid value (not -2 and between 0 and 32) doesn't trigger
    // any violation.
    $entity = EntityTest::create();
    $entity->set($field_name, 1);
    $violations = $entity->validate();
    $this->assertCount(0, $violations, 'No violations found when in-range value passed.');

    // Check that a value that is specifically restricted triggers both
    // violations.
    $entity->set($field_name, -2);
    $violations = $entity->validate();
    $this->assertCount(2, $violations, 'Two violations found when using a null and outside the range value.');

    $this->assertEquals($field_name . '.0.value', $violations[0]->getPropertyPath());
    $this->assertEquals(t('%name does not accept the value @value.', ['%name' => $field_name, '@value' => -2]), $violations[0]->getMessage());

    $this->assertEquals($field_name . '.0.value', $violations[1]->getPropertyPath());
    $this->assertEquals(t('This value should be %limit or more.', ['%limit' => 0]), $violations[1]->getMessage());

    // Check that a value that is not specifically restricted but outside the
    // range triggers the expected violation.
    $entity->set($field_name, 33);
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'Violations found when using value outside the range.');
    $this->assertEquals($field_name . '.0.value', $violations[0]->getPropertyPath());
    $this->assertEquals(t('This value should be %limit or less.', ['%limit' => 32]), $violations[0]->getMessage());
  }

  /**
   * Test creating a field with custom storage set.
   */
  public function testCreateFieldCustomStorage() {
    $field_name = mb_strtolower($this->randomMachineName());
    \Drupal::state()->set('field_test_custom_storage', $field_name);

    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'custom_storage' => TRUE,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => $field_storage->getName(),
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Check that no table has been created for the field.
    $this->assertFalse(\Drupal::database()->schema()->tableExists('entity_test__' . $field_storage->getName()));

    // Save an entity with a value in the custom storage field and verify no
    // data is retrieved on load.
    $entity = EntityTest::create(['name' => $this->randomString(), $field_name => 'Test value']);
    $this->assertIdentical('Test value', $entity->{$field_name}->value, 'The test value is set on the field.');

    $entity->save();
    $entity = EntityTest::load($entity->id());

    $this->assertNull($entity->{$field_name}->value, 'The loaded entity field value is NULL.');
  }

  /**
   * Test reading back a field definition.
   */
  public function testReadField() {
    FieldConfig::create($this->fieldDefinition)->save();

    // Read the field back.
    $field = FieldConfig::load('entity_test.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $this->assertTrue($this->fieldDefinition['field_name'] == $field->getName(), 'The field was properly read.');
    $this->assertTrue($this->fieldDefinition['entity_type'] == $field->getTargetEntityTypeId(), 'The field was properly read.');
    $this->assertTrue($this->fieldDefinition['bundle'] == $field->getTargetBundle(), 'The field was properly read.');
  }

  /**
   * Test the update of a field.
   */
  public function testUpdateField() {
    FieldConfig::create($this->fieldDefinition)->save();

    // Check that basic changes are saved.
    $field = FieldConfig::load('entity_test.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $field->setRequired(!$field->isRequired());
    $field->setLabel($this->randomMachineName());
    $field->set('description', $this->randomMachineName());
    $field->setSetting('test_field_setting', $this->randomMachineName());
    $field->save();

    $field_new = FieldConfig::load('entity_test.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $this->assertEqual($field->isRequired(), $field_new->isRequired(), '"required" change is saved');
    $this->assertEqual($field->getLabel(), $field_new->getLabel(), '"label" change is saved');
    $this->assertEqual($field->getDescription(), $field_new->getDescription(), '"description" change is saved');

    // TODO: test failures.
  }

  /**
   * Test the deletion of a field with no data.
   */
  public function testDeleteFieldNoData() {
    // Deleting and purging fields with data is tested in
    // \Drupal\Tests\field\Kernel\BulkDeleteTest.

    // Create two fields for the same field storage so we can test that only one
    // is deleted.
    FieldConfig::create($this->fieldDefinition)->save();
    $another_field_definition = $this->fieldDefinition;
    $another_field_definition['bundle'] .= '_another_bundle';
    entity_test_create_bundle($another_field_definition['bundle']);
    FieldConfig::create($another_field_definition)->save();

    // Test that the first field is not deleted, and then delete it.
    $field = current(\Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['entity_type' => 'entity_test', 'field_name' => $this->fieldDefinition['field_name'], 'bundle' => $this->fieldDefinition['bundle'], 'include_deleted' => TRUE]));
    $this->assertTrue(!empty($field) && empty($field->deleted), 'A new field is not marked for deletion.');
    $field->delete();

    // Make sure the field was deleted without being marked for purging as there
    // was no data.
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['entity_type' => 'entity_test', 'field_name' => $this->fieldDefinition['field_name'], 'bundle' => $this->fieldDefinition['bundle'], 'include_deleted' => TRUE]);
    $this->assertCount(0, $fields, 'A deleted field is marked for deletion.');

    // Try to load the field normally and make sure it does not show up.
    $field = FieldConfig::load('entity_test.' . '.' . $this->fieldDefinition['bundle'] . '.' . $this->fieldDefinition['field_name']);
    $this->assertTrue(empty($field), 'Field was deleted');

    // Make sure the other field is not deleted.
    $another_field = FieldConfig::load('entity_test.' . $another_field_definition['bundle'] . '.' . $another_field_definition['field_name']);
    $this->assertTrue(!empty($another_field) && !$another_field->isDeleted(), 'A non-deleted field is not marked for deletion.');
  }

  /**
   * Tests the cross deletion behavior between field storages and fields.
   */
  public function testDeleteFieldCrossDeletion() {
    $field_definition_2 = $this->fieldDefinition;
    $field_definition_2['bundle'] .= '_another_bundle';
    entity_test_create_bundle($field_definition_2['bundle']);

    // Check that deletion of a field storage deletes its fields.
    $field_storage = $this->fieldStorage;
    FieldConfig::create($this->fieldDefinition)->save();
    FieldConfig::create($field_definition_2)->save();
    $field_storage->delete();
    $this->assertNull(FieldConfig::loadByName('entity_test', $this->fieldDefinition['bundle'], $field_storage->getName()));
    $this->assertNull(FieldConfig::loadByName('entity_test', $field_definition_2['bundle'], $field_storage->getName()));

    // Check that deletion of the last field deletes the storage.
    $field_storage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $field_storage->save();
    $field = FieldConfig::create($this->fieldDefinition);
    $field->save();
    $field_2 = FieldConfig::create($field_definition_2);
    $field_2->save();
    $field->delete();
    $this->assertNotEmpty(FieldStorageConfig::loadByName('entity_test', $field_storage->getName()));
    $field_2->delete();
    $this->assertNull(FieldStorageConfig::loadByName('entity_test', $field_storage->getName()));

    // Check that deletion of all fields using a storage simultaneously deletes
    // the storage.
    $field_storage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $field_storage->save();
    $field = FieldConfig::create($this->fieldDefinition);
    $field->save();
    $field_2 = FieldConfig::create($field_definition_2);
    $field_2->save();
    $this->container->get('entity_type.manager')->getStorage('field_config')->delete([$field, $field_2]);
    $this->assertNull(FieldStorageConfig::loadByName('entity_test', $field_storage->getName()));
  }

}
