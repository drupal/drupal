<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldTypePluginManagerTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests the field type manager.
 *
 * @group field
 */
class FieldTypePluginManagerTest extends FieldUnitTestBase {

  /**
   * Tests the default settings convenience methods.
   */
  function testDefaultSettings() {
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    foreach (array('test_field', 'shape', 'hidden_test_field') as $type) {
      $definition = $field_type_manager->getDefinition($type);
      $this->assertIdentical($field_type_manager->getDefaultStorageSettings($type), $definition['class']::defaultStorageSettings(), format_string("%type storage settings were returned", array('%type' => $type)));
      $this->assertIdentical($field_type_manager->getDefaultFieldSettings($type), $definition['class']::defaultFieldSettings(), format_string(" %type field settings were returned", array('%type' => $type)));
    }
  }

}
