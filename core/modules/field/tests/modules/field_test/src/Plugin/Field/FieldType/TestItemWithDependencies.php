<?php

declare(strict_types=1);

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'test_field_with_dependencies' entity field item.
 */
#[FieldType(
  id: "test_field_with_dependencies",
  label: new TranslatableMarkup("Test field with dependencies"),
  description: new TranslatableMarkup("Dummy field type used for tests."),
  default_widget: "test_field_widget",
  default_formatter: "field_test_default",
  config_dependencies: [
    "module" => ["system"],
  ]
)]
class TestItemWithDependencies extends TestItem {

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    return ['content' => ['node:article:uuid']];
  }

}
