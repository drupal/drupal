<?php

/**
 * @file
 * Contains \Drupal\field\Tests\TestItemTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Tests the new entity API for the test field type.
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

  public static function getInfo() {
    return array(
      'name' => 'Test field item',
      'description' => 'Tests the new entity API for the test field type.',
      'group' => 'Field types',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create an field field and instance for validation.
    $field = array(
      'name' => $this->field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    entity_create('field_config', $field)->save();
    $instance = array(
      'entity_type' => 'entity_test',
      'field_name' => $this->field_name,
      'bundle' => 'entity_test',
    );
    entity_create('field_instance_config', $instance)->save();
  }

  /**
   * Tests using entity fields of the field field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test');
    $value = rand(1, 10);
    $entity->field_test = $value;
    $entity->name->value = $this->randomName();
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
          'not null' => FALSE,
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(
        'value' => array('value'),
      ),
      'foreign keys' => array(),
    );
    $field_schema = FieldDefinition::create('test_field')->getSchema();
    $this->assertEqual($field_schema, $expected_schema);
  }

}
