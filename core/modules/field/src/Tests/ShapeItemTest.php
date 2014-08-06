<?php

/**
 * @file
 * Contains \Drupal\field\Tests\ShapeItemTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Tests the new entity API for the shape field type.
 *
 * @group field
 */
class ShapeItemTest extends FieldUnitTestBase {

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
  protected $field_name = 'field_shape';

  public function setUp() {
    parent::setUp();

    // Create an field field and instance for validation.
    entity_create('field_storage_config', array(
      'name' => $this->field_name,
      'entity_type' => 'entity_test',
      'type' => 'shape',
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->field_name,
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Tests using entity fields of the field field type.
   */
  public function testShapeItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test');
    $shape = 'cube';
    $color = 'blue';
    $entity->{$this->field_name}->shape = $shape;
    $entity->{$this->field_name}->color = $color;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->{$this->field_name} instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->{$this->field_name}[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->{$this->field_name}->shape, $shape);
    $this->assertEqual($entity->{$this->field_name}->color, $color);
    $this->assertEqual($entity->{$this->field_name}[0]->shape, $shape);
    $this->assertEqual($entity->{$this->field_name}[0]->color, $color);

    // Verify changing the field value.
    $new_shape = 'circle';
    $new_color = 'red';
    $entity->{$this->field_name}->shape = $new_shape;
    $entity->{$this->field_name}->color = $new_color;
    $this->assertEqual($entity->{$this->field_name}->shape, $new_shape);
    $this->assertEqual($entity->{$this->field_name}->color, $new_color);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->{$this->field_name}->shape, $new_shape);
    $this->assertEqual($entity->{$this->field_name}->color, $new_color);
  }

}
