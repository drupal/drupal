<?php

/**
 * @file
 * Definition of Drupal\field_test\Plugin\field\widget\TestFieldWidgetNoDefault.
 */

namespace Drupal\field_test\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'test_field_widget_no_default' widget.
 *
 * @Plugin(
 *   id = "test_field_widget_no_default",
 *   module = "field_test",
 *   label = @Translation("Test widget - no default"),
 *   field_types = {
 *      "test_field"
 *   },
 *   settings = {
 *     "test_widget_setting_multiple" = "dummy test string"
 *   },
 *   default_value = FALSE
 * )
 */
class TestFieldWidgetNoDefault extends TestFieldWidget {}
