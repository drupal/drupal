<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the test field with dependencies type.
 *
 * @group field
 */
class TestItemWithDependenciesTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_test'];

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
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'test_field_with_dependencies',
    ])->save();
    $field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ]);
    $field->save();

    // Validate that the field configuration entity has the expected
    // dependencies.
    $this->assertEqual([
      'content' => ['node:article:uuid'],
      'config' => ['field.storage.entity_test.field_test'],
      'module' => ['entity_test', 'field_test', 'test_module']
    ], $field->getDependencies());
  }

}
