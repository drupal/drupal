<?php

namespace Drupal\editor_test\Plugin\Field\FieldType;

use Drupal\text\Plugin\Field\FieldType\TextLongItem;

/**
 * Plugin implementation of the 'editor_test_text_long' field type.
 *
 * @FieldType(
 *   id = "editor_test_text_long",
 *   label = @Translation("Filter test text (formatted, long)"),
 *   description = @Translation("This field stores a long text with a text format."),
 *   default_widget = "text_textarea",
 *   default_formatter = "text_default"
 * )
 */
class EditorTestTextLongItem extends TextLongItem {

}
