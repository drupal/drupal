<?php

namespace Drupal\field_test\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'test_field_widget_multiple' widget.
 *
 * The 'field_types' entry is left empty, and is populated through
 * hook_field_widget_info_alter().
 *
 * @see field_test_field_widget_info_alter()
 *
 * @FieldWidget(
 *   id = "test_field_widget_multiple_single_value",
 *   label = @Translation("Test widget - multiple - single value"),
 *   multiple_values = FALSE,
 *   weight = 10
 * )
 */
class TestFieldWidgetMultipleSingleValues extends TestFieldWidgetMultiple {

}
