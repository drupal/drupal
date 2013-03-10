<?php

/**
 * @file
 * Contains \Drupal\field\Tests\TestItemTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\Core\Entity\Field\FieldInterface;

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
    $this->field = array(
      'field_name' => 'field_test',
      'type' => 'test_field',
    );
    field_create_field($this->field);
    $this->instance = array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'widget' => array(
        'type' => 'test_field_widget',
      ),
    );
    field_create_instance($this->instance);
  }

  /**
   * Tests using entity fields of the field field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test', array());
    $value = rand(1, 10);
    $entity->field_test = $value;
    $entity->name->value = $this->randomName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_test instanceof FieldInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test->value, $value);
    $this->assertEqual($entity->field_test[0]->value, $value);

    // Verify changing the field value.
    $new_value = rand(1, 10);
    $entity->field_test->value = $new_value;
    $this->assertEqual($entity->field_test->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_test->value, $new_value);
  }

}
