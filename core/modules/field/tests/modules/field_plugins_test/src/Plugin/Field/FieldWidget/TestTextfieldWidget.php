<?php

namespace Drupal\field_plugins_test\Plugin\Field\FieldWidget;

use Drupal\text\Plugin\Field\FieldWidget\TextfieldWidget;

/**
 * Plugin implementation of the 'field_plugins_test_text_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_plugins_test_text_widget",
 *   label = @Translation("Test Text field"),
 *   field_types = {
 *     "text",
 *     "string"
 *   },
 * )
 */
class TestTextfieldWidget extends TextfieldWidget {
}
