<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Entity\Update\SqlContentEntityStorageSchemaColumnTest.
 */

namespace Drupal\field\Tests\Entity\Update;

use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests that schema changes in fields with data are detected during updates.
 *
 * @group Entity
 */
class SqlContentEntityStorageSchemaColumnTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field', 'text', 'user'];

  /**
   * The created entity.
   *
   * @var \Drupal\Core\Entity\Entity
   */
  protected $entity;

  /**
   * The field.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * The field storage.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('user');

    $field_name = 'test';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test_rev',
      'type' => 'string',
      'cardinality' => 1,
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test_rev',
      'bundle' => 'entity_test_rev',
      'required' => TRUE,
    ]);
    $this->field->save();

    // Create an entity with field data.
    $this->entity = EntityTestRev::create([
      'user_id' => mt_rand(1, 10),
      'name' => $this->randomMachineName(),
      $field_name => $this->randomString(),
    ]);
    $this->entity->save();
  }

  /**
   * Tests that column-level schema changes are detected for fields with data.
   */
  public function testColumnUpdate() {
    // Change the field type in the stored schema.
    $schema = \Drupal::keyValue('entity.storage_schema.sql')->get('entity_test_rev.field_schema_data.test');
    $schema['entity_test_rev__test']['fields']['test_value']['type'] = 'varchar_ascii';
    \Drupal::keyValue('entity.storage_schema.sql')->set('entity_test_rev.field_schema_data.test', $schema);

    // Now attempt to run automatic updates. An exception should be thrown
    // since there is data in the table.
    try {
      \Drupal::service('entity.definition_update_manager')->applyUpdates();
      $this->fail('Failed to detect a schema change in a field with data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('Detected a schema change in a field with data.');
    }
  }

}
