<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Kernel;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests dependencies of entity displays.
 */
#[Group('field_ui')]
#[RunTestsInSeparateProcesses]
class EntityDisplayDependenciesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'field_dynamic_dependencies_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['field', 'user']);
  }

  /**
   * Tests that entity display dependencies are calculated from multiple fields.
   */
  public function testDisplaysWithMultipleDependencies(): void {
    // Set up two field components, each dependent on different arbitrary
    // modules that should not already be dependencies otherwise.
    $dependent_fields = [
      'test_field' => 'action',
      'test_field_2' => 'aggregator',
    ];
    $this->addDefaultTestFields(array_keys($dependent_fields));

    // Now that the fields exist, set up the display.
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $this->addFieldsToDisplay($dependent_fields, $display);
    $this->assertDisplayDependencies($dependent_fields, $display);

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $this->addFieldsToDisplay($dependent_fields, $form_display);
    $this->assertDisplayDependencies($dependent_fields, $form_display);
  }

  /**
   * Adds default test fields to an entity.
   *
   * @param array $field_names
   *   The field names.
   */
  protected function addDefaultTestFields(array $field_names): void {
    foreach ($field_names as $field_name) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'entity_test',
        'type' => 'test_field_dynamic_dependencies',
      ]);
      $field_storage->save();
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => 'entity_test',
      ]);
      $field->save();
    }
  }

  /**
   * Adds fields to an entity display.
   *
   * @param array $dependent_fields
   *   An array of field names to configure on the display, mapped to module
   *   names to be used as dependencies.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   An entity display to configure and test.
   */
  protected function addFieldsToDisplay(array $dependent_fields, EntityDisplayInterface $display): void {
    foreach ($dependent_fields as $field_name => $dependency) {
      $display->setComponent($field_name, [
        'type' => 'test_field_dynamic_dependencies',
        'weight' => 0,
        'settings' => [
          'dependent_module' => $dependency,
        ],
      ]);
    }
  }

  /**
   * Asserts that a display has all dependencies from its fields.
   *
   * @param array $dependent_fields
   *   An array of field names to configure on the display, mapped to module
   *   names to be used as dependencies.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   An entity display to configure and test.
   */
  protected function assertDisplayDependencies(array $dependent_fields, EntityDisplayInterface $display): void {
    $dependencies = $display->calculateDependencies()->getDependencies();
    foreach ($dependent_fields as $module) {
      $this->assertContains($module, $dependencies['module']);
    }
  }

}
