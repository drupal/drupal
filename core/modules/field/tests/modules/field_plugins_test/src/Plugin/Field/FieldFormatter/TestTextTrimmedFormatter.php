<?php

namespace Drupal\field_plugins_test\Plugin\Field\FieldFormatter;

use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;

/**
 * Plugin implementation of the 'field_plugins_test_text_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_plugins_test_text_formatter",
 *   label = @Translation("Test Trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class TestTextTrimmedFormatter extends TextTrimmedFormatter {
}
