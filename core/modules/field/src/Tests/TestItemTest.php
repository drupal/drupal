<?php

/**
 * @file
 * Contains \Drupal\field\Tests\TestItemTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Tests the new entity API for the test field type.
 *
 * @group field
 */
class TestItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  /**
   * The name of the field to use in this test.
   *
   * @var string
   */
  protected $field_name = 'field_test';

  protected function setUp() {
    parent::setUp();

    // Create a 'test_field' field and storage for validation.
    entity_create('field_storage_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->field_name,
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Tests using entity fields of the field field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test');
    $value = rand(1, 10);
    $entity->field_test = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->{$this->field_name} instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->{$this->field_name}[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->{$this->field_name}->value, $value);
    $this->assertEqual($entity->{$this->field_name}[0]->value, $value);

    // Verify changing the field value.
    $new_value = rand(1, 10);
    $entity->field_test->value = $new_value;
    $this->assertEqual($entity->{$this->field_name}->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->{$this->field_name}->value, $new_value);

    // Test the schema for this field type.
    $expected_schema = array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'medium',
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(
        'value' => array('value'),
      ),
      'foreign keys' => array(),
    );
    $field_schema = BaseFieldDefinition::create('test_field')->getSchema();
    $this->assertEqual($field_schema, $expected_schema);
  }

}
