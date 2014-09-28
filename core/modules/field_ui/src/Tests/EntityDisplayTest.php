<?php

/**
 * @file
 * Contains \Drupal\field_ui\Tests\EntityDisplayTest.
 */

namespace Drupal\field_ui\Tests;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the entity display configuration entities.
 *
 * @group field_ui
 */
class EntityDisplayTest extends KernelTestBase {

  public static $modules = array('field_ui', 'field', 'entity_test', 'user', 'text', 'field_test', 'node', 'system');

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('field'));
  }

  /**
   * Tests basic CRUD operations on entity display objects.
   */
  public function testEntityDisplayCRUD() {
    $display = entity_create('entity_view_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ));

    $expected = array();

    // Check that providing no 'weight' results in the highest current weight
    // being assigned. The 'name' field's formatter has weight -5, therefore
    // these follow.
    $expected['component_1'] = array('weight' => -4);
    $expected['component_2'] = array('weight' => -3);
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
    $display = entity_load('entity_view_display', $display->id());
    foreach (array('component_1', 'component_2', 'component_3') as $name) {
      $this->assertEqual($display->getComponent($name), $expected[$name]);
    }

    // Ensure that third party settings were added to the config entity.
    // These are added by entity_test_entity_presave() implemented in
    // entity_test module.
    $this->assertEqual('bar', $display->getThirdPartySetting('entity_test', 'foo'), 'Third party settings were added to the entity view display.');

    // Check that getComponents() returns options for all components.
    $expected['name'] = array(
      'label' => 'hidden',
      'type' => 'string',
      'weight' => -5,
      'settings' => array(),
      'third_party_settings' => array(),
    );
    $this->assertEqual($display->getComponents(), $expected);

    // Check that a component can be removed.
    $display->removeComponent('component_3');
    $this->assertNULL($display->getComponent('component_3'));

    // Check that the removal is correctly persisted.
    $display->save();
    $display = entity_load('entity_view_display', $display->id());
    $this->assertNULL($display->getComponent('component_3'));

    // Check that CreateCopy() creates a new component that can be correclty
    // saved.
    EntityViewMode::create(array('id' => $display->targetEntityType . '.other_view_mode', 'targetEntityType' => $display->targetEntityType))->save();
    $new_display = $display->createCopy('other_view_mode');
    $new_display->save();
    $new_display = entity_load('entity_view_display', $new_display->id());
    $dependencies = $new_display->calculateDependencies();
    $this->assertEqual(array('entity' => array('core.entity_view_mode.entity_test.other_view_mode'), 'module' => array('entity_test')), $dependencies);
    $this->assertEqual($new_display->targetEntityType, $display->targetEntityType);
    $this->assertEqual($new_display->bundle, $display->bundle);
    $this->assertEqual($new_display->mode, 'other_view_mode');
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
   * Tests the behavior of a field component within an entity display object.
   */
  public function testExtraFieldComponent() {
    entity_test_create_bundle('bundle_with_extra_fields');
    $display = entity_create('entity_view_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'bundle_with_extra_fields',
      'mode' => 'default',
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
   * Tests the behavior of a field component within an entity display object.
   */
  public function testFieldComponent() {
    $field_name = 'test_field';
    // Create a field storage and a field.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    $display = entity_create('entity_view_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ));

    // Check that providing no options results in default values being used.
    $display->setComponent($field_name);
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_storage->type);
    $default_formatter = $field_type_info['default_formatter'];
    $formatter_settings =  \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings($default_formatter);
    $expected = array(
      'weight' => -4,
      'label' => 'above',
      'type' => $default_formatter,
      'settings' => $formatter_settings,
      'third_party_settings' => array(),
    );
    $this->assertEqual($display->getComponent($field_name), $expected);

    // Check that the getFormatter() method returns the correct formatter plugin.
    $formatter = $display->getRenderer($field_name);
    $this->assertEqual($formatter->getPluginId(), $default_formatter);
    $this->assertEqual($formatter->getSettings(), $formatter_settings);

    // Check that the formatter is statically persisted, by assigning an
    // arbitrary property and reading it back.
    $random_value = $this->randomString();
    $formatter->randomValue = $random_value;
    $formatter = $display->getRenderer($field_name);
    $this->assertEqual($formatter->randomValue, $random_value);

    // Check that changing the definition creates a new formatter.
    $display->setComponent($field_name, array(
      'type' => 'field_test_multiple',
    ));
    $formatter = $display->getRenderer($field_name);
    $this->assertEqual($formatter->getPluginId(), 'field_test_multiple');
    $this->assertFalse(isset($formatter->randomValue));

    // Check that the display has dependencies on the field and the module that
    // provides the formatter.
    $dependencies = $display->calculateDependencies();
    $this->assertEqual(array('entity' => array('field.field.entity_test.entity_test.test_field'), 'module' => array('entity_test', 'field_test')), $dependencies);
  }

  /**
   * Tests the behavior of a field component for a base field.
   */
  public function testBaseFieldComponent() {
    $display = entity_create('entity_view_display', array(
      'targetEntityType' => 'entity_test_base_field_display',
      'bundle' => 'entity_test_base_field_display',
      'mode' => 'default',
    ));

    // Check that default options are correctly filled in.
    $formatter_settings =  \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('text_default');
    $expected = array(
      'test_no_display' => NULL,
      'test_display_configurable' => array(
        'label' => 'above',
        'type' => 'text_default',
        'settings' => $formatter_settings,
        'third_party_settings' => array(),
        'weight' => 10,
      ),
      'test_display_non_configurable' => array(
        'label' => 'above',
        'type' => 'text_default',
        'settings' => $formatter_settings,
        'third_party_settings' => array(),
        'weight' => 11,
      ),
    );
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }

    // Check that saving the display only writes data for fields whose display
    // is configurable.
    $display->save();
    $config = \Drupal::config('core.entity_view_display.' . $display->id());
    $data = $config->get();
    $this->assertFalse(isset($data['content']['test_no_display']));
    $this->assertFalse(isset($data['hidden']['test_no_display']));
    $this->assertEqual($data['content']['test_display_configurable'], $expected['test_display_configurable']);
    $this->assertFalse(isset($data['content']['test_display_non_configurable']));
    $this->assertFalse(isset($data['hidden']['test_display_non_configurable']));

    // Check that defaults are correctly filled when loading the display.
    $display = entity_load('entity_view_display', $display->id());
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }

    // Check that data manually written for fields whose display is not
    // configurable is discarded when loading the display.
    $data['content']['test_display_non_configurable'] = $expected['test_display_non_configurable'];
    $data['content']['test_display_non_configurable']['weight']++;
    $config->setData($data)->save();
    $display = entity_load('entity_view_display', $display->id());
    foreach ($expected as $field_name => $options) {
      $this->assertEqual($display->getComponent($field_name), $options);
    }
  }

  /**
   * Tests renaming and deleting a bundle.
   */
  public function testRenameDeleteBundle() {
    $this->installEntitySchema('node');

    // Create a node bundle, display and form display object.
    entity_create('node_type', array('type' => 'article'))->save();
    entity_get_display('node', 'article', 'default')->save();
    entity_get_form_display('node', 'article', 'default')->save();

    // Rename the article bundle and assert the entity display is renamed.
    $type = node_type_load('article');
    $type->old_type = 'article';
    $type->type = 'article_rename';
    $type->save();
    $old_display = entity_load('entity_view_display', 'node.article.default');
    $this->assertFalse((bool) $old_display);
    $old_form_display = entity_load('entity_form_display', 'node.article.default');
    $this->assertFalse((bool) $old_form_display);
    $new_display = entity_load('entity_view_display', 'node.article_rename.default');
    $this->assertEqual('article_rename', $new_display->bundle);
    $this->assertEqual('node.article_rename.default', $new_display->id);
    $new_form_display = entity_load('entity_form_display', 'node.article_rename.default');
    $this->assertEqual('article_rename', $new_form_display->bundle);
    $this->assertEqual('node.article_rename.default', $new_form_display->id);

    $expected_view_dependencies = array(
      'entity' => array('field.field.node.article_rename.body', 'node.type.article_rename'),
      'module' => array('entity_test', 'text', 'user')
    );
    // Check that the display has dependencies on the bundle, fields and the
    // modules that provide the formatters.
    $dependencies = $new_display->calculateDependencies();
    $this->assertEqual($expected_view_dependencies, $dependencies);

    // Check that the form display has dependencies on the bundle, fields and
    // the modules that provide the formatters.
    $dependencies = $new_form_display->calculateDependencies();
    $expected_form_dependencies = array(
      'entity' => array('field.field.node.article_rename.body', 'node.type.article_rename'),
      'module' => array('text')
    );
    $this->assertEqual($expected_form_dependencies, $dependencies);

    // Delete the bundle.
    $type->delete();
    $display = entity_load('entity_view_display', 'node.article_rename.default');
    $this->assertFalse((bool) $display);
    $form_display = entity_load('entity_form_display', 'node.article_rename.default');
    $this->assertFalse((bool) $form_display);
  }

  /**
   * Tests deleting field.
   */
  public function testDeleteField() {
    $field_name = 'test_field';
    // Create a field storage and a field.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    // Create default and teaser entity display.
    EntityViewMode::create(array('id' =>  'entity_test.teaser', 'targetEntityType' => 'entity_test'))->save();
    entity_create('entity_view_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ))->setComponent($field_name)->save();
    entity_create('entity_view_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'teaser',
    ))->setComponent($field_name)->save();

    // Check the component exists.
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertTrue($display->getComponent($field_name));
    $display = entity_get_display('entity_test', 'entity_test', 'teaser');
    $this->assertTrue($display->getComponent($field_name));

    // Delete the field.
    $field->delete();

    // Check that the component has been removed from the entity displays.
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($display->getComponent($field_name));
    $display = entity_get_display('entity_test', 'entity_test', 'teaser');
    $this->assertFalse($display->getComponent($field_name));
  }

  /**
   * Tests \Drupal\entity\EntityDisplayBase::onDependencyRemoval().
   */
  public function testOnDependencyRemoval() {
    $this->enableModules(array('field_plugins_test'));

    $field_name = 'test_field';
    // Create a field.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'text'
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    entity_create('entity_view_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ))->setComponent($field_name, array('type' => 'field_plugins_test_text_formatter'))->save();

    // Check the component exists and is of the correct type.
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertEqual($display->getComponent($field_name)['type'], 'field_plugins_test_text_formatter');

    // Removing the field_plugins_test module should change the component to use
    // the default formatter for test fields.
    \Drupal::service('config.manager')->uninstall('module', 'field_plugins_test');
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertEqual($display->getComponent($field_name)['type'], 'text_default');

    // Removing the text module should remove the field from the view display.
    \Drupal::service('config.manager')->uninstall('module', 'text');
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($display->getComponent($field_name));
  }
}
