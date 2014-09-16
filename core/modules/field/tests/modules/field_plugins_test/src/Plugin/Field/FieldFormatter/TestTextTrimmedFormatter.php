<?php

/**
 * @file
 * Contains \Drupal\field_plugins_test\Plugin\Field\FieldFormatter\TextTrimmedFormatter.
 */

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
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class TestTextTrimmedFormatter extends TextTrimmedFormatter {
}
