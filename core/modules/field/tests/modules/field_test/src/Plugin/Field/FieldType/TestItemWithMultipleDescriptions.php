<?php

declare(strict_types=1);

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'test_field_with_multiple_descriptions' entity field item.
 */
#[FieldType(
  id: "test_field_with_multiple_descriptions",
  label: new TranslatableMarkup("Test field (multiple descriptions"),
  description: [
    new TranslatableMarkup("This multiple line description needs to use an array"),
    new TranslatableMarkup("This second line contains important information"),
  ],
  category: "field_test_descriptions",
  default_widget: "test_field_widget",
  default_formatter: "field_test_default"
)]
class TestItemWithMultipleDescriptions extends TestItem {
}
