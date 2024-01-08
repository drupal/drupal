<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the field type manager.
 *
 * @group field
 */
class FieldTypePluginManagerTest extends FieldKernelTestBase {

  /**
   * Tests the default settings convenience methods.
   */
  public function testDefaultSettings() {
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    foreach (['test_field', 'shape', 'hidden_test_field'] as $type) {
      $definition = $field_type_manager->getDefinition($type);
      $this->assertSame($field_type_manager->getDefaultStorageSettings($type), $definition['class']::defaultStorageSettings(), "$type storage settings were returned");
      $this->assertSame($field_type_manager->getDefaultFieldSettings($type), $definition['class']::defaultFieldSettings(), "$type field settings were returned");
    }
  }

  /**
   * Tests creation of field item instances.
   */
  public function testCreateInstance() {
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    foreach (['test_field', 'shape', 'hidden_test_field'] as $type) {
      $definition = $field_type_manager->getDefinition($type);

      $class = $definition['class'];
      $field_name = 'field_' . $type;

      $field_definition = BaseFieldDefinition::create($type);

      $configuration = [
        'field_definition' => $field_definition,
        'name' => $field_name,
        'parent' => NULL,
      ];

      $instance = $field_type_manager->createInstance($type, $configuration);

      $this->assertInstanceOf($class, $instance);
      $this->assertEquals($field_name, $instance->getName(), "Instance name is $field_name");
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

    $configuration = [
      'field_definition' => $field_definition,
      'name' => $field_name,
      'parent' => NULL,
    ];

    $entity = EntityTest::create();

    $instance = $field_type_manager->createInstance($type, $configuration);

    $this->assertInstanceOf($class, $instance);
    $this->assertEquals($field_name, $instance->getName(), "Instance name is $field_name");
    $this->assertEquals('Jenny', $instance->getFieldDefinition()->getLabel(), 'Instance label is Jenny');
    $this->assertEquals([['value' => 8675309]], $instance->getFieldDefinition()->getDefaultValue($entity), 'Instance default_value is 8675309');
  }

  /**
   * Tests all field items provide an existing main property.
   */
  public function testMainProperty() {
    // Let's enable all Drupal modules in Drupal core, so we test any field
    // type plugin.
    $this->enableAllCoreModules();

    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    foreach ($field_type_manager->getDefinitions() as $plugin_id => $definition) {
      $class = $definition['class'];
      $property = $class::mainPropertyName();
      if ($property === NULL) {
        continue;
      }
      $storage_definition = BaseFieldDefinition::create($plugin_id);
      $property_definitions = $class::propertyDefinitions($storage_definition);
      $properties = implode(', ', array_keys($property_definitions));
      if (!empty($property_definitions)) {
        $message = sprintf("%s property %s found in %s", $plugin_id, $property, $properties);
        $this->assertArrayHasKey($property, $class::propertyDefinitions($storage_definition), $message);
      }
    }
  }

  /**
   * Enable all core modules.
   */
  protected function enableAllCoreModules() {
    $listing = new ExtensionDiscovery($this->root);
    $module_list = $listing->scan('module', FALSE);
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $module_list = array_filter(array_keys($module_list), function ($module) use ($module_handler, $module_list) {
      return !$module_handler->moduleExists($module) && str_starts_with($module_list[$module]->getPath(), 'core');
    });
    $this->enableModules($module_list);
  }

}
