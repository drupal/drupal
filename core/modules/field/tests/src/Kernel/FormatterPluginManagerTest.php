<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Tests the field formatter plugin manager.
 *
 * @group field
 */
class FormatterPluginManagerTest extends FieldKernelTestBase {

  /**
   * Tests that getInstance falls back on default if current is not applicable.
   *
   * @see \Drupal\field\Tests\WidgetPluginManagerTest::testNotApplicableFallback()
   */
  public function testNotApplicableFallback() {
    /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager */
    $formatter_plugin_manager = \Drupal::service('plugin.manager.field.formatter');

    $base_field_definition = BaseFieldDefinition::create('test_field')
      // Set a name that will make isApplicable() return TRUE.
      ->setName('field_test_field');

    $formatter_options = [
      'field_definition' => $base_field_definition,
      'view_mode' => 'default',
      'configuration' => [
        'type' => 'field_test_applicable',
      ],
    ];

    $instance = $formatter_plugin_manager->getInstance($formatter_options);
    $this->assertEquals('field_test_applicable', $instance->getPluginId());

    // Now set name to something that makes isApplicable() return FALSE.
    $base_field_definition->setName('deny_applicable');
    $instance = $formatter_plugin_manager->getInstance($formatter_options);

    // Instance should be default widget.
    $this->assertNotSame('field_test_applicable', $instance->getPluginId());
    $this->assertEquals('field_test_default', $instance->getPluginId());
  }

}
