<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Tests the field widget manager.
 *
 * @group field
 */
class WidgetPluginManagerTest extends FieldKernelTestBase {

  /**
   * Tests that the widget definitions alter hook works.
   */
  public function testWidgetDefinitionAlter() {
    $widget_definition = \Drupal::service('plugin.manager.field.widget')->getDefinition('test_field_widget_multiple');

    // Test if hook_field_widget_info_alter is being called.
    $this->assertTrue(in_array('test_field', $widget_definition['field_types']), "The 'test_field_widget_multiple' widget is enabled for the 'test_field' field type in field_test_field_widget_info_alter().");
  }

  /**
   * Tests that getInstance falls back on default if current is not applicable.
   *
   * @see \Drupal\field\Tests\FormatterPluginManagerTest::testNotApplicableFallback()
   */
  public function testNotApplicableFallback() {
    /** @var \Drupal\Core\Field\WidgetPluginManager $widget_plugin_manager */
    $widget_plugin_manager = \Drupal::service('plugin.manager.field.widget');

    $base_field_definition = BaseFieldDefinition::create('test_field')
      // Set a name that will make isApplicable() return TRUE.
      ->setName('field_multiwidgetfield');

    $widget_options = [
      'field_definition' => $base_field_definition,
      'form_mode' => 'default',
      'configuration' => [
        'type' => 'test_field_widget_multiple',
      ],
    ];

    $instance = $widget_plugin_manager->getInstance($widget_options);
    $this->assertEqual($instance->getPluginId(), 'test_field_widget_multiple');

    // Now do the same but with machine name field_onewidgetfield, because that
    // makes isApplicable() return FALSE.
    $base_field_definition->setName('field_onewidgetfield');
    $instance = $widget_plugin_manager->getInstance($widget_options);

    // Instance should be default widget.
    $this->assertNotEqual($instance->getPluginId(), 'test_field_widget_multiple');
    $this->assertEqual($instance->getPluginId(), 'test_field_widget');
  }

}
