<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldTypePluginManagerTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests the field type manager.
 */
class FieldTypePluginManagerTest extends FieldUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Field type manager',
      'description' => 'Tests the field type manager',
      'group' => 'Field API',
    );
  }

  /**
   * Tests the default settings convenience methods.
   */
  function testDefaultSettings() {
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    foreach (array('test_field', 'shape', 'hidden_test_field') as $type) {
      $definition = $field_type_manager->getDefinition($type);
      $this->assertIdentical($field_type_manager->getDefaultSettings($type), $definition['class']::defaultSettings(), format_string("field settings service returns %type's field settings", array('%type' => $type)));
      $this->assertIdentical($field_type_manager->getDefaultInstanceSettings($type), $definition['class']::defaultInstanceSettings(), format_string("field instance settings service returns %type's field instance settings", array('%type' => $type)));
    }
  }

}
