<?php

/**
 * @file
 * Contains \Drupal\field\Tests\ShapeItemTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Tests the new entity API for the shape field type.
 */
class ShapeItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  public static function getInfo() {
    return array(
      'name' => 'Shape field item',
      'description' => 'Tests the new entity API for the shape field type.',
      'group' => 'Field types',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create an field field and instance for validation.
    $this->field = array(
      'field_name' => 'field_shape',
      'type' => 'shape',
    );
    field_create_field($this->field);
    $this->instance = array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_shape',
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
  public function testShapeItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test', array());
    $shape = 'cube';
    $color = 'blue';
    $entity->field_shape->shape = $shape;
    $entity->field_shape->color = $color;
    $entity->name->value = $this->randomName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_shape instanceof FieldInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_shape[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_shape->shape, $shape);
    $this->assertEqual($entity->field_shape->color, $color);
    $this->assertEqual($entity->field_shape[0]->shape, $shape);
    $this->assertEqual($entity->field_shape[0]->color, $color);

    // Verify changing the field value.
    $new_shape = 'circle';
    $new_color = 'red';
    $entity->field_shape->shape = $new_shape;
    $entity->field_shape->color = $new_color;
    $this->assertEqual($entity->field_shape->shape, $new_shape);
    $this->assertEqual($entity->field_shape->color, $new_color);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_shape->shape, $new_shape);
    $this->assertEqual($entity->field_shape->color, $new_color);
  }

}
