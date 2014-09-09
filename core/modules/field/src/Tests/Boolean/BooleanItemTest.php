<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Boolean\BooleanItemTest.
 */

namespace Drupal\field\Tests\Boolean;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests the new entity API for the boolean field type.
 *
 * @group field
 */
class BooleanItemTest extends FieldUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an boolean field and instance for validation.
    entity_create('field_storage_config', array(
      'name' => 'field_boolean',
      'entity_type' => 'entity_test',
      'type' => 'boolean',
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_boolean',
      'bundle' => 'entity_test',
    ))->save();

    // Create a form display for the default form mode.
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent('field_boolean', array(
        'type' => 'boolean',
      ))
      ->save();
  }

  /**
   * Tests using entity fields of the boolean field type.
   */
  public function testBooleanItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test');
    $value = '1';
    $entity->field_boolean = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_boolean instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_boolean[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_boolean->value, $value);
    $this->assertEqual($entity->field_boolean[0]->value, $value);

    // Verify changing the boolean value.
    $new_value = 0;
    $entity->field_boolean->value = $new_value;
    $this->assertEqual($entity->field_boolean->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_boolean->value, $new_value);

    // Test sample item generation.
    $entity = entity_create('entity_test');
    $entity->field_boolean->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
