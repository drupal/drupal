<?php

/**
 * @file
 * Contains \Drupal\field\Tests\WidgetManagerTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests the field widget manager.
 *
 * @group field
 */
class WidgetPluginManagerTest extends FieldUnitTestBase {

  /**
   * Tests that the widget definitions alter hook works.
   */
  function testWidgetDefinitionAlter() {
    $widget_definition = \Drupal::service('plugin.manager.field.widget')->getDefinition('test_field_widget_multiple');

    // Test if hook_field_widget_info_alter is beïng called.
    $this->assertTrue(in_array('test_field', $widget_definition['field_types']), "The 'test_field_widget_multiple' widget is enabled for the 'test_field' field type in field_test_field_widget_info_alter().");
  }

}
