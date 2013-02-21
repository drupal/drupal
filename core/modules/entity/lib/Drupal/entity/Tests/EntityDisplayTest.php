<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\EntityDisplayTest.
 */

namespace Drupal\entity\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the EntityDisplay configuration entities.
 */
class EntityDisplayTest extends DrupalUnitTestBase {

  public static $modules = array('entity', 'field', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity display configuration entities',
      'description' => 'Tests the entity display configuration entities.',
      'group' => 'Entity API',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->installSchema('field', array('field_config', 'field_config_instance'));
  }

  /**
   * Tests basic CRUD operations on EntityDisplay objects.
   */
  public function testEntityDisplayCRUD() {
    $display = entity_create('entity_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'viewMode' => 'default',
    ));

    $expected = array();

    // Check that providing no 'weight' results in the highest current weight
    // being assigned.
    $expected['component_1'] = array('weight' => 0);
    $expected['component_2'] = array('weight' => 1);
    $display->setComponent('component_1');
    $display->setComponent('component_2');
    $this->assertEqual($display->getComponent('component_1'), $expected['component_1']);
    $this->assertEqual($display->getComponent('component_2'), $expected['component_2']);

    // Check that arbitrary options are correctly stored.
    $expected['component_3'] = array('weight' => 10, 'foo' => 'bar');
    $display->setComponent('component_3', $expected['component_3']);
    $this->assertEqual($display->getComponent('component_3'), $expected['component_3']);

    // Check that the display can be properly saved and read back.
    $display->save();
    $display = entity_load('entity_display', $display->id());
    foreach (array('component_1', 'component_2', 'component_3') as $name) {
      $this->assertEqual($display->getComponent($name), $expected[$name]);
    }

    // Check that getComponents() returns options for all components.
    $this->assertEqual($display->getComponents(), $expected);

    // Check that a component can be removed.
    $display->removeComponent('component_3');
    $this->assertNULL($display->getComponent('component_3'));

    // Check that the removal is correctly persisted.
    $display->save();
    $display = entity_load('entity_display', $display->id());
    $this->assertNULL($display->getComponent('component_3'));

    // Check that CreateCopy() creates a new component that can be correclty
    // saved.
    $new_display = $display->createCopy('other_view_mode');
    $new_display->save();
    $new_display = entity_load('entity_display', $new_display->id());
    $this->assertEqual($new_display->targetEntityType, $display->targetEntityType);
    $this->assertEqual($new_display->bundle, $display->bundle);
    $this->assertEqual($new_display->viewMode, 'other_view_mode');
    $this->assertEqual($new_display->getComponents(), $display->getComponents());
  }

  /**
   * Tests entity_get_display().
   */
  public function testEntityGetDisplay() {
    // Check that entity_get_display() returns a fresh object when no
    // configuration entry exists.
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertTrue($display->isNew());

    // Add some components and save the display.
    $display->setComponent('component_1', array('weight' => 10))
      ->save();

    // Check that entity_get_display() returns the correct object.
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($display->isNew());
    $this->assertEqual($display->id, 'entity_test.entity_test.default');
    $this->assertEqual($display->getComponent('component_1'), array('weight' => 10));
  }

  /**
   * Tests the behavior of a field component within an EntityDisplay object.
   */
  public function testExtraFieldComponent() {
    $display = entity_create('entity_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'viewMode' => 'default',
    ));

    // Check that the default visibility taken into account for extra fields
    // unknown in the display.
    $this->assertEqual($display->getComponent('display_extra_field'), array('weight' => 5));
    $this->assertNull($display->getComponent('display_extra_field_hidden'));

    // Check that setting explicit options overrides the defaults.
    $display->removeComponent('display_extra_field');
    $display->setComponent('display_extra_field_hidden', array('weight' => 10));
    $this->assertNull($display->getComponent('display_extra_field'));
    $this->assertEqual($display->getComponent('display_extra_field_hidden'), array('weight' => 10));
  }

  /**
   * Tests the behavior of a field component within an EntityDisplay object.
   */
  public function testFieldComponent() {
    $this->enableModules(array('field_sql_storage', 'field_test'));

    $display = entity_create('entity_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'viewMode' => 'default',
    ));

    // Create a field and an instance.
    $field = array(
      'field_name' => 'test_field',
      'type' => 'test_field'
    );
    field_create_field($field);
    $instance = array(
      'field_name' => $field['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    field_create_instance($instance);

    // Check that providing no options results in default values being used.
    $display->setComponent($field['field_name']);
    $field_type_info = field_info_field_types($field['type']);
    $default_formatter = $field_type_info['default_formatter'];
    $default_settings = field_info_formatter_settings($default_formatter);
    $expected = array(
      'weight' => 0,
      'label' => 'above',
      'type' => $default_formatter,
      'settings' => $default_settings,
    );
    $this->assertEqual($display->getComponent($field['field_name']), $expected);

    // Check that the getFormatter() method returns the correct formatter plugin.
    $formatter = $display->getFormatter($field['field_name']);
    $this->assertEqual($formatter->getPluginId(), $default_formatter);
    $this->assertEqual($formatter->getSettings(), $default_settings);

    // Check that the formatter is statically persisted, by assigning an
    // arbitrary property and reading it back.
    $random_value = $this->randomString();
    $formatter->randomValue = $random_value;
    $formatter = $display->getFormatter($field['field_name']);
    $this->assertEqual($formatter->randomValue, $random_value );

    // Check that changing the definition creates a new formatter.
    $display->setComponent($field['field_name'], array(
      'type' => 'field_test_multiple',
    ));
    $formatter = $display->getFormatter($field['field_name']);
    $this->assertEqual($formatter->getPluginId(), 'field_test_multiple');
    $this->assertFalse(isset($formatter->randomValue));

    // Check that specifying an unknown formatter (e.g. case of a disabled
    // module) gets stored as is in the display, but results in the default
    // formatter being used.
    $display->setComponent($field['field_name'], array(
      'type' => 'unknown_formatter',
    ));
    $options = $display->getComponent($field['field_name']);
    $this->assertEqual($options['type'], 'unknown_formatter');
    $formatter = $display->getFormatter($field['field_name']);
    $this->assertEqual($formatter->getPluginId(), $default_formatter);
  }

}
