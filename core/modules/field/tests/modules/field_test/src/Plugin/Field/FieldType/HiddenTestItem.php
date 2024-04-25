<?php

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'hidden_test' entity field item.
 */
#[FieldType(
  id: "hidden_test_field",
  label: new TranslatableMarkup("Hidden from UI test field"),
  description: new TranslatableMarkup("Dummy hidden field type used for tests."),
  default_widget: "test_field_widget",
  default_formatter: "field_test_default",
  no_ui: TRUE
)]
class HiddenTestItem extends TestItem {

}
