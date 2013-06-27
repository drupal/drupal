<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\EntityFormDisplayTest.
 */

namespace Drupal\entity\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the EntityDisplay configuration entities.
 */
class EntityFormDisplayTest extends DrupalUnitTestBase {

  public static $modules = array('entity', 'field', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity form display configuration entities',
      'description' => 'Tests the entity form display configuration entities.',
      'group' => 'Entity API',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('field'));
  }

  /**
   * Tests entity_get_form_display().
   */
  public function testEntityGetFromDisplay() {
    // Check that entity_get_form_display() returns a fresh object when no
    // configuration entry exists.
    $form_display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertTrue($form_display->isNew());

    // Add some components and save the display.
    $form_display->setComponent('component_1', array('weight' => 10))
      ->save();

    // Check that entity_get_form_display() returns the correct object.
    $form_display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($form_display->isNew());
    $this->assertEqual($form_display->id, 'entity_test.entity_test.default');
    $this->assertEqual($form_display->getComponent('component_1'), array('weight' => 10));
  }

  /**
   * Tests the behavior of a field component within an EntityFormDisplay object.
   */
  public function testFieldComponent() {
    $this->enableModules(array('field_sql_storage', 'field_test'));

    $form_display = entity_create('entity_form_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ));

    // Create a field and an instance.
    $field_name = 'test_field';
    $field = entity_create('field_entity', array(
      'field_name' => $field_name,
      'type' => 'test_field'
    ));
    $field->save();
    $instance = entity_create('field_instance', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ));
    $instance->save();

    // Check that providing no options results in default values being used.
    $form_display->setComponent($field_name);
    $field_type_info = field_info_field_types($field->type);
    $default_widget = $field_type_info['default_widget'];
    $default_settings = field_info_widget_settings($default_widget);
    $expected = array(
      'weight' => 0,
      'type' => $default_widget,
      'settings' => $default_settings,
    );
    $this->assertEqual($form_display->getComponent($field_name), $expected);

    // Check that the getWidget() method returns the correct widget plugin.
    $widget = $form_display->getRenderer($field_name);
    $this->assertEqual($widget->getPluginId(), $default_widget);
    $this->assertEqual($widget->getSettings(), $default_settings);

    // Check that the widget is statically persisted, by assigning an
    // arbitrary property and reading it back.
    $random_value = $this->randomString();
    $widget->randomValue = $random_value;
    $widget = $form_display->getRenderer($field_name);
    $this->assertEqual($widget->randomValue, $random_value);

    // Check that changing the definition creates a new widget.
    $form_display->setComponent($field_name, array(
      'type' => 'field_test_multiple',
    ));
    $widget = $form_display->getRenderer($field_name);
    $this->assertEqual($widget->getPluginId(), 'test_field_widget');
    $this->assertFalse(isset($widget->randomValue));

    // Check that specifying an unknown widget (e.g. case of a disabled module)
    // gets stored as is in the display, but results in the default widget being
    // used.
    $form_display->setComponent($field_name, array(
      'type' => 'unknown_widget',
    ));
    $options = $form_display->getComponent($field_name);
    $this->assertEqual($options['type'], 'unknown_widget');
    $widget = $form_display->getRenderer($field_name);
    $this->assertEqual($widget->getPluginId(), $default_widget);
  }

}
