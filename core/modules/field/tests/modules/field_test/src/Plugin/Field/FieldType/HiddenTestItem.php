<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Field\FieldType\HiddenTestItem.
 */

namespace Drupal\field_test\Plugin\Field\FieldType;

/**
 * Defines the 'hidden_test' entity field item.
 *
 * @FieldType(
 *   id = "hidden_test_field",
 *   label = @Translation("Hidden from UI test field"),
 *   description = @Translation("Dummy hidden field type used for tests."),
 *   no_ui = TRUE,
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class HiddenTestItem extends TestItem {

}
