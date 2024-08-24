<?php

declare(strict_types=1);

namespace Drupal\field_plugins_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\text\Plugin\Field\FieldWidget\TextfieldWidget;

/**
 * Plugin implementation of the 'field_plugins_test_text_widget' widget.
 */
#[FieldWidget(
  id: 'field_plugins_test_text_widget',
  label: new TranslatableMarkup('Test Text field'),
  field_types: [
    'text',
    'string',
  ],
)]
class TestTextfieldWidget extends TextfieldWidget {
}
