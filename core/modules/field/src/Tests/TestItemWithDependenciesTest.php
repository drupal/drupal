<?php

/**
 * @file
 * Contains \Drupal\field\Tests\TestItemWithDependenciesTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests the new entity API for the test field with dependencies type.
 *
 * @group field
 */
class TestItemWithDependenciesTest extends FieldUnitTestBase {

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
  protected $fieldName = 'field_test';

  /**
   * Tests that field types can add dependencies to field config entities.
   */
  public function testTestItemWithDepenencies() {
    // Create a 'test_field_with_dependencies' field and storage for validation.
    entity_create('field_storage_config', array(
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'test_field_with_dependencies',
    ))->save();
    $field = entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ));
    $field->save();

    // Validate that the field configuration entity has the expected
    // dependencies.
    $this->assertEqual([
      'content' => ['node:article:uuid'],
      'config' => ['field.storage.entity_test.field_test'],
      'module' => ['field_test', 'test_module']
    ], $field->getDependencies());
  }

}
