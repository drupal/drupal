<?php

namespace Drupal\Tests\field_ui\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the entity display configuration entities.
 *
 * @group field_ui
 */
class EntityFormDisplayTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = ['field_ui', 'field', 'entity_test', 'field_test', 'user', 'text'];

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
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
    $form_display->setComponent('component_1', ['weight' => 10])
      ->save();

    // Check that entity_get_form_display() returns the correct object.
    $form_display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($form_display->isNew());
    $this->assertEqual($form_display->id(), 'entity_test.entity_test.default');
    $this->assertEqual($form_display->getComponent('component_1'), ['weight' => 10, 'settings' => [], 'third_party_settings' => [], 'region' => 'content']);
  }

  /**
   * Tests the behavior of a field component within an EntityFormDisplay object.
   */
  public function testFieldComponent() {
    // Create a field storage and a field.
    $field_name = 'test_field';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);

    // Check that providing no options results in default values being used.
    $form_display->setComponent($field_name);
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_storage->getType());
    $default_widget = $field_type_info['default_widget'];
    $widget_settings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings($default_widget);
    $expected = [
      'weight' => 3,
      'type' => $default_widget,
      'settings' => $widget_settings,
      'third_party_settings' => [],
    ];
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
    $form_display->setComponent($field_name, [
      'type' => 'field_test_multiple',
    ]);
    $widget = $form_display->getRenderer($field_name);
    $this->assertEqual($widget->getPluginId(), 'test_field_widget');
    $this->assertFalse(isset($widget->randomValue));

    // Check that specifying an unknown widget (e.g. case of a disabled module)
    // gets stored as is in the display, but results in the default widget being
    // used.
    $form_display->setComponent($field_name, [
      'type' => 'unknown_widget',
    ]);
    $options = $form_display->getComponent($field_name);
    $this->assertEqual($options['type'], 'unknown_widget');
    $widget = $form_display->getRenderer($field_name);
    $this->assertEqual($widget->getPluginId(), $default_widget);
  }

  /**
   * Tests the behavior of a field component for a base field.
   */
  public function testBaseFieldComponent() {
    $display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test_base_field_display',
      'bundle' => 'entity_test_base_field_display',
      'mode' => 'default',
    ]);

    // Check that default options are correctly filled in.
    $formatter_settings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings('text_textfield');
    $expected = [
      'test_no_display' => NULL,
      'test_display_configurable' => [
        'type' => 'text_textfield',
        'settings' => $formatter_settings,
        'third_party_settings' => [],
        'weight' => 10,
        'region' => 'content',
      ],
      'test_display_non_configurable' => [
        'type' => 'text_textfield',
        'settings' => $formatter_settings,
        'third_party_settings' => [],
        'weight' => 11,
        'region' => 'content',
      ],
    ];
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }

    // Check that saving the display only writes data for fields whose display
    // is configurable.
    $display->save();
    $config = $this->config('core.entity_form_display.' . $display->id());
    $data = $config->get();
    $this->assertFalse(isset($data['content']['test_no_display']));
    $this->assertFalse(isset($data['hidden']['test_no_display']));
    $this->assertEqual($data['content']['test_display_configurable'], $expected['test_display_configurable']);
    $this->assertFalse(isset($data['content']['test_display_non_configurable']));
    $this->assertFalse(isset($data['hidden']['test_display_non_configurable']));

    // Check that defaults are correctly filled when loading the display.
    $display = EntityFormDisplay::load($display->id());
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }

    // Check that data manually written for fields whose display is not
    // configurable is discarded when loading the display.
    $data['content']['test_display_non_configurable'] = $expected['test_display_non_configurable'];
    $data['content']['test_display_non_configurable']['weight']++;
    $config->setData($data)->save();
    $display = EntityFormDisplay::load($display->id());
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }
  }

  /**
   * Tests deleting field.
   */
  public function testDeleteField() {
    $field_name = 'test_field';
    // Create a field storage and a field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field->save();

    // Create default and compact entity display.
    EntityFormMode::create(['id' => 'entity_test.compact', 'targetEntityType' => 'entity_test'])->save();
    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->setComponent($field_name)->save();
    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'compact',
    ])->setComponent($field_name)->save();

    // Check the component exists.
    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertTrue($display->getComponent($field_name));
    $display = entity_get_form_display('entity_test', 'entity_test', 'compact');
    $this->assertTrue($display->getComponent($field_name));

    // Delete the field.
    $field->delete();

    // Check that the component has been removed from the entity displays.
    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($display->getComponent($field_name));
    $display = entity_get_form_display('entity_test', 'entity_test', 'compact');
    $this->assertFalse($display->getComponent($field_name));
  }

  /**
   * Tests \Drupal\Core\Entity\EntityDisplayBase::onDependencyRemoval().
   */
  public function testOnDependencyRemoval() {
    $this->enableModules(['field_plugins_test']);

    $field_name = 'test_field';
    // Create a field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'text',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field->save();

    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->setComponent($field_name, ['type' => 'field_plugins_test_text_widget'])->save();

    // Check the component exists and is of the correct type.
    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertEqual($display->getComponent($field_name)['type'], 'field_plugins_test_text_widget');

    // Removing the field_plugins_test module should change the component to use
    // the default widget for test fields.
    \Drupal::service('config.manager')->uninstall('module', 'field_plugins_test');
    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertEqual($display->getComponent($field_name)['type'], 'text_textfield');

    // Removing the text module should remove the field from the form display.
    \Drupal::service('config.manager')->uninstall('module', 'text');
    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($display->getComponent($field_name));
  }

}
