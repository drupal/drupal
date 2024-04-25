<?php

declare(strict_types=1);

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'test_field_with_single_description' entity field item.
 */
#[FieldType(
  id: "test_field_with_single_description",
  label: new TranslatableMarkup("Test field (single description"),
  description: new TranslatableMarkup("This one-line field description is important for testing"),
  category: "field_test_descriptions",
  default_widget: "test_field_widget",
  default_formatter: "field_test_default"
)]
class TestItemWithSingleDescription extends TestItem {
}
