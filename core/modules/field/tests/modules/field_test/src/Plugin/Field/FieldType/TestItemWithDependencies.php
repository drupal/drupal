<?php

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the 'test_field_with_dependencies' entity field item.
 *
 * @FieldType(
 *   id = "test_field_with_dependencies",
 *   label = @Translation("Test field with dependencies"),
 *   description = @Translation("Dummy field type used for tests."),
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default",
 *   config_dependencies = {
 *     "module" = {
 *       "test_module"
 *     }
 *   }
 * )
 */
class TestItemWithDependencies extends TestItem {

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    return ['content' => ['node:article:uuid']];
  }

}
