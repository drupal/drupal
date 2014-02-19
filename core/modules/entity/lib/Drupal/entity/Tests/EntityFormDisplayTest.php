<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\EntityFormDisplayTest.
 */

namespace Drupal\entity\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the entity display configuration entities.
 */
class EntityFormDisplayTest extends DrupalUnitTestBase {

  public static $modules = array('entity', 'field', 'entity_test', 'user', 'text');

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
    $this->enableModules(array('field_test'));

    // Create a field and an instance.
    $field_name = 'test_field';
    $field = entity_create('field_config', array(
      'name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ));
    $field->save();
    $instance = entity_create('field_instance_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ));
    $instance->save();

    $form_display = entity_create('entity_form_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ));

    // Check that providing no options results in default values being used.
    $form_display->setComponent($field_name);
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field->type);
    $default_widget = $field_type_info['default_widget'];
    $widget_settings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings($default_widget);
    $expected = array(
      'weight' => 0,
      'type' => $default_widget,
      'settings' => $widget_settings,
    );
    $this->assertEqual($form_display->getComponent($field_name), $expected);

    // Check that the getWidget() method returns the correct widget plugin.
    $widget = $form_display->getRenderer($field_name);
    $this->assertEqual($widget->getPluginId(), $default_widget);
    $this->assertEqual($widget->getSettings(), $widget_settings);

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

  /**
   * Tests the behavior of a field component for a base field.
   */
  public function testBaseFieldComponent() {
    $display = entity_create('entity_form_display', array(
      'targetEntityType' => 'entity_test_base_field_display',
      'bundle' => 'entity_test_base_field_display',
      'mode' => 'default',
    ));

    // Check that default options are correctly filled in.
    $formatter_settings =  \Drupal::service('plugin.manager.field.widget')->getDefaultSettings('text_textfield');
    $expected = array(
      'test_no_display' => NULL,
      'test_display_configurable' => array(
        'type' => 'text_textfield',
        'settings' => $formatter_settings,
        'weight' => 10,
      ),
      'test_display_non_configurable' => array(
        'type' => 'text_textfield',
        'settings' => $formatter_settings,
        'weight' => 11,
      ),
    );
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }

    // Check that saving the display only writes data for fields whose display
    // is configurable.
    $display->save();
    $config = \Drupal::config('entity.form_display.' . $display->id());
    $data = $config->get();
    $this->assertFalse(isset($data['content']['test_no_display']));
    $this->assertFalse(isset($data['hidden']['test_no_display']));
    $this->assertEqual($data['content']['test_display_configurable'], $expected['test_display_configurable']);
    $this->assertFalse(isset($data['content']['test_display_non_configurable']));
    $this->assertFalse(isset($data['hidden']['test_display_non_configurable']));

    // Check that defaults are correctly filled when loading the display.
    $display = entity_load('entity_form_display', $display->id());
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }

    // Check that data manually written for fields whose display is not
    // configurable is discarded when loading the display.
    $data['content']['test_display_non_configurable'] = $expected['test_display_non_configurable'];
    $data['content']['test_display_non_configurable']['weight']++;
    $config->setData($data)->save();
    $display = entity_load('entity_form_display', $display->id());
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }
  }

  /**
   * Tests deleting field instance.
   */
  public function testDeleteFieldInstance() {
    $this->enableModules(array('field_sql_storage', 'field_test'));

    $field_name = 'test_field';
    // Create a field and an instance.
    $field = entity_create('field_config', array(
      'name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ));
    $field->save();
    $instance = entity_create('field_instance_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ));
    $instance->save();

    // Create default and compact entity display.
    entity_create('entity_form_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ))->setComponent($field_name)->save();
    entity_create('entity_form_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'compact',
    ))->setComponent($field_name)->save();

    // Check the component exists.
    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertTrue($display->getComponent($field_name));
    $display = entity_get_form_display('entity_test', 'entity_test', 'compact');
    $this->assertTrue($display->getComponent($field_name));

    // Delete the instance.
    $instance->delete();

    // Check that the component has been removed from the entity displays.
    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($display->getComponent($field_name));
    $display = entity_get_form_display('entity_test', 'entity_test', 'compact');
    $this->assertFalse($display->getComponent($field_name));
  }

}
