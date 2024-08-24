<?php

declare(strict_types=1);

namespace Drupal\field_plugins_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;

/**
 * Plugin implementation of the 'field_plugins_test_text_formatter' formatter.
 */
#[FieldFormatter(
  id: 'field_plugins_test_text_formatter',
  label: new TranslatableMarkup('Test Trimmed'),
  field_types: [
    'text',
    'text_long',
    'text_with_summary',
  ],
)]
class TestTextTrimmedFormatter extends TextTrimmedFormatter {
}
