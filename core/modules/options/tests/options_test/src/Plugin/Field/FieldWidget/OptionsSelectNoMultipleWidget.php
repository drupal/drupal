<?php

declare(strict_types=1);

namespace Drupal\options_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Widget extending OptionsSelectWidget without declaring multiple_values.
 */
#[FieldWidget(
  id: 'options_select_no_multiple',
  label: new TranslatableMarkup('Options select (no multiple_values)'),
  field_types: ['list_integer', 'list_float', 'list_string'],
)]
class OptionsSelectNoMultipleWidget extends OptionsSelectWidget {
}
