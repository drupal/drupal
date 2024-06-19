<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Field\FieldException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests field storage create, read, update, and delete.
 *
 * @group field
 * @group #slow
 */
class FieldStorageCrudTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [];

  // @todo Test creation with
  // - a full fledged $field structure, check that all the values are there
  // - a minimal $field structure, check all default values are set
  // defer actual $field comparison to a helper function, used for the two cases above

  /**
   * Tests the creation of a field storage.
   */
  public function testCreate(): void {
    $field_storage_definition = [
      'field_name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ];
    field_test_memorize();
    $field_storage = FieldStorageConfig::create($field_storage_definition);
    $field_storage->save();

    $field_storage = FieldStorageConfig::load($field_storage->id());
    $this->assertEquals('TRUE', $field_storage->getSetting('storage_setting_from_config_data'));
    $this->assertNull($field_storage->getSetting('config_data_from_storage_setting'));

    $mem = field_test_memorize();
    $this->assertSame($field_storage_definition['field_name'], $mem['field_test_field_storage_config_create'][0][0]->getName(), 'hook_entity_create() called with correct arguments.');
    $this->assertSame($field_storage_definition['type'], $mem['field_test_field_storage_config_create'][0][0]->getType(), 'hook_entity_create() called with correct arguments.');

    // Read the configuration. Check against raw configuration data rather than
    // the loaded ConfigEntity, to be sure we check that the defaults are
    // applied on write.
    $field_storage_config = $this->config('field.storage.' . $field_storage->id())->get();

    $this->assertTrue($field_storage_config['settings']['config_data_from_storage_setting']);
    $this->assertTrue(!isset($field_storage_config['settings']['storage_setting_from_config_data']));

    // Since we are working with raw configuration, this needs to be unset
    // manually.
    // @see Drupal\field_test\Plugin\Field\FieldType\TestItem::storageSettingsFromConfigData()
    unset($field_storage_config['settings']['config_data_from_storage_setting']);

    // Ensure that basic properties are preserved.
    $this->assertEquals($field_storage_definition['field_name'], $field_storage_config['field_name'], 'The field name is properly saved.');
    $this->assertEquals($field_storage_definition['entity_type'], $field_storage_config['entity_type'], 'The field entity type is properly saved.');
    $this->assertEquals($field_storage_definition['entity_type'] . '.' . $field_storage_definition['field_name'], $field_storage_config['id'], 'The field id is properly saved.');
    $this->assertEquals($field_storage_definition['type'], $field_storage_config['type'], 'The field type is properly saved.');

    // Ensure that cardinality defaults to 1.
    $this->assertEquals(1, $field_storage_config['cardinality'], 'Cardinality defaults to 1.');

    // Ensure that default settings are present.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $this->assertEquals($field_type_manager->getDefaultStorageSettings($field_storage_definition['type']), $field_storage_config['settings'], 'Default storage settings have been written.');

    // Guarantee that the name is unique.
    try {
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create two fields with the same name.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(EntityStorageException::class, $e);
    }

    // Check that field type is required.
    try {
      $field_storage_definition = [
        'field_name' => 'field_1',
        'entity_type' => 'entity_type',
      ];
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create a field with no type.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }

    // Check that field name is required.
    try {
      $field_storage_definition = [
        'type' => 'test_field',
        'entity_type' => 'entity_test',
      ];
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create an unnamed field.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }
    // Check that entity type is required.
    try {
      $field_storage_definition = [
        'field_name' => 'test_field',
        'type' => 'test_field',
      ];
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create a field without an entity type.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }

    // Check that field name must start with a letter or _.
    try {
      $field_storage_definition = [
        'field_name' => '2field_2',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      ];
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create a field with a name starting with a digit.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }

    // Check that field name must only contain lowercase alphanumeric or _.
    try {
      $field_storage_definition = [
        'field_name' => 'field#_3',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      ];
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create a field with a name containing an illegal character.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }

    // Check that field name cannot be longer than 32 characters long.
    try {
      $field_storage_definition = [
        'field_name' => '_12345678901234567890123456789012',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      ];
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create a field with a name longer than 32 characters.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }

    // Check that field name can not be an entity key.
    // "id" is known as an entity key from the "entity_test" type.
    try {
      $field_storage_definition = [
        'type' => 'test_field',
        'field_name' => 'id',
        'entity_type' => 'entity_test',
      ];
      FieldStorageConfig::create($field_storage_definition)->save();
      $this->fail('Cannot create a field bearing the name of an entity key.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }
  }

  /**
   * Tests that an explicit schema can be provided on creation.
   *
   * This behavior is needed to allow field storage creation within updates,
   * since plugin classes (and thus the field type schema) cannot be accessed.
   */
  public function testCreateWithExplicitSchema(): void {
    $schema = [
      'dummy' => 'foobar',
    ];
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'schema' => $schema,
    ]);
    $this->assertEquals($schema, $field_storage->getSchema());
  }

  /**
   * Tests reading field storage definitions.
   */
  public function testRead(): void {
    $field_storage_definition = [
      'field_name' => 'field_1',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ];
    $field_storage = FieldStorageConfig::create($field_storage_definition);
    $field_storage->save();
    $id = $field_storage->id();

    // Check that 'single column' criteria works.
    $field_storage_config_storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    $fields = $field_storage_config_storage->loadByProperties(['field_name' => $field_storage_definition['field_name']]);
    $this->assertCount(1, $fields, 'The field was properly read.');
    $this->assertArrayHasKey($id, $fields, 'The field has the correct key.');

    // Check that 'multi column' criteria works.
    $fields = $field_storage_config_storage->loadByProperties([
      'field_name' => $field_storage_definition['field_name'],
      'type' => $field_storage_definition['type'],
      'entity_type' => $field_storage_definition['entity_type'],
    ]);
    $this->assertCount(1, $fields, 'The field was properly read.');
    $this->assertArrayHasKey($id, $fields, 'The field has the correct key.');
    $fields = $field_storage_config_storage->loadByProperties(['field_name' => $field_storage_definition['field_name'], 'type' => 'foo']);
    $this->assertEmpty($fields, 'No field was found.');

    // Create a field from the field storage.
    $field_definition = [
      'field_name' => $field_storage_definition['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field_definition)->save();
  }

  /**
   * Tests creation of indexes on data column.
   */
  public function testIndexes(): void {
    // Check that indexes specified by the field type are used by default.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_1',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);
    $field_storage->save();
    $field_storage = FieldStorageConfig::load($field_storage->id());
    $schema = $field_storage->getSchema();
    $expected_indexes = ['value' => ['value']];
    $this->assertEquals($expected_indexes, $schema['indexes'], 'Field type indexes saved by default');

    // Check that indexes specified by the field definition override the field
    // type indexes.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'indexes' => [
        'value' => [],
      ],
    ]);
    $field_storage->save();
    $field_storage = FieldStorageConfig::load($field_storage->id());
    $schema = $field_storage->getSchema();
    $expected_indexes = ['value' => []];
    $this->assertEquals($expected_indexes, $schema['indexes'], 'Field definition indexes override field type indexes');

    // Check that indexes specified by the field definition add to the field
    // type indexes.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_3',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'indexes' => [
        'value_2' => ['value'],
      ],
    ]);
    $field_storage->save();
    $id = $field_storage->id();
    $field_storage = FieldStorageConfig::load($id);
    $schema = $field_storage->getSchema();
    $expected_indexes = ['value' => ['value'], 'value_2' => ['value']];
    $this->assertEquals($expected_indexes, $schema['indexes'], 'Field definition indexes are merged with field type indexes');
  }

  /**
   * Tests the deletion of a field storage.
   */
  public function testDeleteNoData(): void {
    // Deleting and purging field storages with data is tested in
    // \Drupal\Tests\field\Kernel\BulkDeleteTest.

    // Create two fields (so we can test that only one is deleted).
    $field_storage_definition = [
      'field_name' => 'field_1',
      'type' => 'test_field',
      'entity_type' => 'entity_test',
    ];
    FieldStorageConfig::create($field_storage_definition)->save();
    $another_field_storage_definition = [
      'field_name' => 'field_2',
      'type' => 'test_field',
      'entity_type' => 'entity_test',
    ];
    FieldStorageConfig::create($another_field_storage_definition)->save();

    // Create fields for each.
    $field_definition = [
      'field_name' => $field_storage_definition['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field_definition)->save();
    $another_field_definition = $field_definition;
    $another_field_definition['field_name'] = $another_field_storage_definition['field_name'];
    FieldConfig::create($another_field_definition)->save();

    // Test that the first field is not deleted, and then delete it.
    $field_storage_config_storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    $field_storage = current($field_storage_config_storage->loadByProperties(['field_name' => $field_storage_definition['field_name'], 'include_deleted' => TRUE]));
    $this->assertFalse($field_storage->isDeleted());
    FieldStorageConfig::loadByName('entity_test', $field_storage_definition['field_name'])->delete();

    // Make sure that the field storage is deleted as it had no data.
    $field_storages = $field_storage_config_storage->loadByProperties(['field_name' => $field_storage_definition['field_name'], 'include_deleted' => TRUE]);
    $this->assertCount(0, $field_storages, 'Field storage was deleted');

    // Make sure that this field is marked as deleted when it is
    // specifically loaded.
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['entity_type' => 'entity_test', 'field_name' => $field_definition['field_name'], 'bundle' => $field_definition['bundle'], 'include_deleted' => TRUE]);
    $this->assertCount(0, $fields, 'Field storage was deleted');

    // Try to load the storage normally and make sure it does not show up.
    $field_storage = FieldStorageConfig::load('entity_test.' . $field_storage_definition['field_name']);
    $this->assertEmpty($field_storage, 'Field storage was deleted');

    // Try to load the field normally and make sure it does not show up.
    $field = FieldConfig::load('entity_test.' . '.' . $field_definition['bundle'] . '.' . $field_definition['field_name']);
    $this->assertEmpty($field, 'Field was deleted');

    // Make sure the other field and its storage are not deleted.
    $another_field_storage = FieldStorageConfig::load('entity_test.' . $another_field_storage_definition['field_name']);
    $this->assertFalse($another_field_storage->isDeleted());
    $another_field = FieldConfig::load('entity_test.' . $another_field_definition['bundle'] . '.' . $another_field_definition['field_name']);
    $this->assertFalse($another_field->isDeleted());

    // Try to create a new field the same name as a deleted field and
    // write data into it.
    FieldStorageConfig::create($field_storage_definition)->save();
    FieldConfig::create($field_definition)->save();
    $field_storage = FieldStorageConfig::load('entity_test.' . $field_storage_definition['field_name']);
    $this->assertFalse($field_storage->isDeleted());
    $field = FieldConfig::load('entity_test.' . $field_definition['bundle'] . '.' . $field_definition['field_name']);
    $this->assertFalse($field->isDeleted());

    // Save an entity with data for the field
    $entity = EntityTest::create();
    $values[0]['value'] = mt_rand(1, 127);
    $entity->{$field_storage->getName()}->value = $values[0]['value'];
    $entity = $this->entitySaveReload($entity);

    // Verify the field is present on load
    $this->assertCount(1, $entity->{$field_storage->getName()}, "Data in previously deleted field saves and loads correctly");
    foreach ($values as $delta => $value) {
      $this->assertEquals($values[$delta]['value'], $entity->{$field_storage->getName()}[$delta]->value, "Data in previously deleted field saves and loads correctly");
    }
  }

  public function testUpdateFieldType(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_type',
      'entity_type' => 'entity_test',
      'type' => 'decimal',
    ]);
    $field_storage->save();

    try {
      $field_storage->set('type', 'integer');
      $field_storage->save();
      $this->fail('Cannot update a field to a different type.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldException::class, $e);
    }
  }

  /**
   * Tests changing a field storage type.
   */
  public function testUpdateEntityType(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_type',
      'entity_type' => 'entity_test',
      'type' => 'decimal',
    ]);
    $field_storage->save();

    $this->expectException(FieldException::class);
    $this->expectExceptionMessage('Cannot change the field type for an existing field storage. The field storage entity_test.field_type has the type decimal.');

    $field_storage->set('type', 'foobar');
    $field_storage->save();
  }

  /**
   * Tests changing a field storage entity type.
   */
  public function testUpdateEntityTargetType(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_type',
      'entity_type' => 'entity_test',
      'type' => 'decimal',
    ]);
    $field_storage->save();

    $this->expectException(FieldException::class);
    $this->expectExceptionMessage('Cannot change the entity type for an existing field storage. The field storage foobar.field_type has the type entity_test.');

    $field_storage->set('entity_type', 'foobar');
    $field_storage->save();
  }

  /**
   * Tests updating a field storage.
   */
  public function testUpdate(): void {
    // Create a field with a defined cardinality, so that we can ensure it's
    // respected. Since cardinality enforcement is consistent across database
    // systems, it makes a good test case.
    $cardinality = 4;
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_update',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => $cardinality,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    do {
      $entity = EntityTest::create();
      // Fill in the entity with more values than $cardinality.
      for ($i = 0; $i < 20; $i++) {
        // We can not use $i here because 0 values are filtered out.
        $entity->field_update[] = $i + 1;
      }
      // Load back and assert there are $cardinality number of values.
      $entity = $this->entitySaveReload($entity);
      $this->assertCount($field_storage->getCardinality(), $entity->field_update);
      // Now check the values themselves.
      for ($delta = 0; $delta < $cardinality; $delta++) {
        $this->assertEquals($delta + 1, $entity->field_update[$delta]->value);
      }
      // Increase $cardinality and set the field cardinality to the new value.
      $field_storage->setCardinality(++$cardinality);
      $field_storage->save();
    } while ($cardinality < 6);
  }

  /**
   * Tests field type modules forbidding an update.
   */
  public function testUpdateForbid(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'forbidden',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'settings' => [
        'changeable' => 0,
        'unchangeable' => 0,
      ],
    ]);
    $field_storage->save();
    $field_storage->setSetting('changeable', $field_storage->getSetting('changeable') + 1);
    try {
      $field_storage->save();
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->fail('An unchangeable setting cannot be updated.');
    }
    $field_storage->setSetting('unchangeable', $field_storage->getSetting('unchangeable') + 1);
    try {
      $field_storage->save();
      $this->fail('An unchangeable setting can be updated.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(FieldStorageDefinitionUpdateForbiddenException::class, $e);
    }
  }

}
