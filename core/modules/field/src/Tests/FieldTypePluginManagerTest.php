<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldTypePluginManagerTest.
 */

namespace Drupal\field\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

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

  /**
   * Tests creation of field item instances.
   */
  public function testCreateInstance() {
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    foreach (array('test_field', 'shape', 'hidden_test_field') as $type) {
      $definition = $field_type_manager->getDefinition($type);

      $class = $definition['class'];
      $field_name = 'field_' . $type;

      $field_definition = BaseFieldDefinition::create($type);

      $configuration = array(
        'field_definition' => $field_definition,
        'name' => $field_name,
        'parent' => NULL,
      );

      $instance = $field_type_manager->createInstance($type, $configuration);

      $this->assertTrue($instance instanceof $class, String::format('Created a @class instance', array('@class' => $class)));
      $this->assertEqual($field_name, $instance->getName(), String::format('Instance name is @name', array('@name' => $field_name)));
    }
  }

  /**
   * Tests creation of field item instances.
   */
  public function testCreateInstanceWithConfig() {
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $type = 'test_field';
    $definition = $field_type_manager->getDefinition($type);

    $class = $definition['class'];
    $field_name = 'field_' . $type;

    $field_definition = BaseFieldDefinition::create($type)
      ->setLabel('Jenny')
      ->setDefaultValue(8675309);

    $configuration = array(
      'field_definition' => $field_definition,
      'name' => $field_name,
      'parent' => NULL,
    );

    $entity = EntityTest::create();

    $instance = $field_type_manager->createInstance($type, $configuration);

    $this->assertTrue($instance instanceof $class, String::format('Created a @class instance', array('@class' => $class)));
    $this->assertEqual($field_name, $instance->getName(), String::format('Instance name is @name', array('@name' => $field_name)));
    $this->assertEqual($instance->getFieldDefinition()->getLabel(), 'Jenny', 'Instance label is Jenny');
    $this->assertEqual($instance->getFieldDefinition()->getDefaultValue($entity), [['value' => 8675309]], 'Instance default_value is 8675309');
  }

}
