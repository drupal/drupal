<?php

/**
 * @file
 * Contains \Drupal\field_ui\Tests\EntityDisplayTest.
 */

namespace Drupal\field_ui\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\KernelTestBase;
use Drupal\Tests\Core\Entity\EntityManagerTest;

/**
 * Tests the entity display configuration entities.
 *
 * @group field_ui
 */
class EntityDisplayTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = array('field_ui', 'field', 'entity_test', 'user', 'text', 'field_test', 'node', 'system');

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(array('field', 'node', 'user'));
  }

  /**
   * Tests basic CRUD operations on entity display objects.
   */
  public function testEntityDisplayCRUD() {
    $display = EntityViewDisplay::create(array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ));

    $expected = array();

    // Check that providing no 'weight' results in the highest current weight
    // being assigned. The 'name' field's formatter has weight -5, therefore
    // these follow.
    $expected['component_1'] = array('weight' => -4, 'settings' => array(), 'third_party_settings' => array());
    $expected['component_2'] = array('weight' => -3, 'settings' => array(), 'third_party_settings' => array());
    $display->setComponent('component_1');
    $display->setComponent('component_2');
    $this->assertEqual($display->getComponent('component_1'), $expected['component_1']);
    $this->assertEqual($display->getComponent('component_2'), $expected['component_2']);

    // Check that arbitrary options are correctly stored.
    $expected['component_3'] = array('weight' => 10, 'third_party_settings' => array('field_test' => array('foo' => 'bar')), 'settings' => array());
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
      'settings' => array(
        'link_to_entity' => FALSE,
      ),
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

    // Check that createCopy() creates a new component that can be correctly
    // saved.
    EntityViewMode::create(array('id' => $display->getTargetEntityTypeId() . '.other_view_mode', 'targetEntityType' => $display->getTargetEntityTypeId()))->save();
    $new_display = $display->createCopy('other_view_mode');
    $new_display->save();
    $new_display = entity_load('entity_view_display', $new_display->id());
    $dependencies = $new_display->calculateDependencies();
    $this->assertEqual(array('config' => array('core.entity_view_mode.entity_test.other_view_mode'), 'module' => array('entity_test')), $dependencies);
    $this->assertEqual($new_display->getTargetEntityTypeId(), $display->getTargetEntityTypeId());
    $this->assertEqual($new_display->getTargetBundle(), $display->getTargetBundle());
    $this->assertEqual($new_display->getMode(), 'other_view_mode');
    $this->assertEqual($new_display->getComponents(), $display->getComponents());
  }

  /**
   *  Test sorting of components by name on basic CRUD operations
   */
  public function testEntityDisplayCRUDSort() {
    $display = EntityViewDisplay::create(array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ));
    $display->setComponent('component_3');
    $display->setComponent('component_1');
    $display->setComponent('component_2');
    $display->save();
    $components = array_keys($display->getComponents());
    // The name field is not configurable so will be added automatically.
    $expected = array ( 0 => 'component_1', 1 => 'component_2',  2 => 'component_3', 'name');
    $this->assertIdentical($components, $expected);
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
    $display->setComponent('component_1', array('weight' => 10, 'settings' => array()))
      ->save();

    // Check that entity_get_display() returns the correct object.
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertFalse($display->isNew());
    $this->assertEqual($display->id(), 'entity_test.entity_test.default');
    $this->assertEqual($display->getComponent('component_1'), array( 'weight' => 10, 'settings' => array(), 'third_party_settings' => array()));
  }

  /**
   * Tests the behavior of a field component within an entity display object.
   */
  public function testExtraFieldComponent() {
    entity_test_create_bundle('bundle_with_extra_fields');
    $display = EntityViewDisplay::create(array(
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
    $this->assertEqual($display->getComponent('display_extra_field_hidden'), array('weight' => 10, 'settings' => array(), 'third_party_settings' => array()));
  }

  /**
   * Tests the behavior of a field component within an entity display object.
   */
  public function testFieldComponent() {
    $field_name = 'test_field';
    // Create a field storage and a field.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    $display = EntityViewDisplay::create(array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ));

    // Check that providing no options results in default values being used.
    $display->setComponent($field_name);
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_storage->getType());
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
    $this->assertEqual(array('config' => array('field.field.entity_test.entity_test.test_field'), 'module' => array('entity_test', 'field_test')), $dependencies);
  }

  /**
   * Tests the behavior of a field component for a base field.
   */
  public function testBaseFieldComponent() {
    $display = EntityViewDisplay::create(array(
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
    $config = $this->config('core.entity_view_display.' . $display->id());
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
   * Tests deleting a bundle.
   */
  public function testDeleteBundle() {
    // Create a node bundle, display and form display object.
    $type = NodeType::create(array('type' => 'article'));
    $type->save();
    node_add_body_field($type);
    entity_get_display('node', 'article', 'default')->save();
    entity_get_form_display('node', 'article', 'default')->save();

    // Delete the bundle.
    $type->delete();
    $display = entity_load('entity_view_display', 'node.article.default');
    $this->assertFalse((bool) $display);
    $form_display = entity_load('entity_form_display', 'node.article.default');
    $this->assertFalse((bool) $form_display);
  }

  /**
   * Tests deleting field.
   */
  public function testDeleteField() {
    $field_name = 'test_field';
    // Create a field storage and a field.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    // Create default and teaser entity display.
    EntityViewMode::create(array('id' =>  'entity_test.teaser', 'targetEntityType' => 'entity_test'))->save();
    EntityViewDisplay::create(array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ))->setComponent($field_name)->save();
    EntityViewDisplay::create(array(
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
   * Tests \Drupal\Core\Entity\EntityDisplayBase::onDependencyRemoval().
   */
  public function testOnDependencyRemoval() {
    $this->enableModules(array('field_plugins_test'));

    $field_name = 'test_field';
    // Create a field.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'text'
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    EntityViewDisplay::create(array(
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

  /**
   * Ensure that entity view display changes invalidates cache tags.
   */
  public function testEntityDisplayInvalidateCacheTags() {
    $cache = \Drupal::cache();
    $cache->set('cid', 'kittens', Cache::PERMANENT, ['config:entity_view_display_list']);
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $display->setComponent('kitten');
    $display->save();
    $this->assertFalse($cache->get('cid'));
  }

  /**
   * Test getDisplayModeOptions().
   */
  public function testGetDisplayModeOptions() {
    NodeType::create(array('type' => 'article'))->save();

    EntityViewDisplay::create(array(
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'default',
    ))->setStatus(TRUE)->save();

    $display_teaser = EntityViewDisplay::create(array(
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'teaser',
    ));
    $display_teaser->save();

    EntityFormDisplay::create(array(
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'default',
    ))->setStatus(TRUE)->save();

    $form_display_teaser = EntityFormDisplay::create(array(
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'register',
    ));
    $form_display_teaser->save();

    // Test getViewModeOptionsByBundle().
    $view_modes = \Drupal::entityManager()->getViewModeOptionsByBundle('node', 'article');
    $this->assertEqual($view_modes, array('default' => 'Default'));
    $display_teaser->setStatus(TRUE)->save();
    $view_modes = \Drupal::entityManager()->getViewModeOptionsByBundle('node', 'article');
    $this->assertEqual($view_modes, array('default' => 'Default', 'teaser' => 'Teaser'));

    // Test getFormModeOptionsByBundle().
    $form_modes = \Drupal::entityManager()->getFormModeOptionsByBundle('user', 'user');
    $this->assertEqual($form_modes, array('default' => 'Default'));
    $form_display_teaser->setStatus(TRUE)->save();
    $form_modes = \Drupal::entityManager()->getFormModeOptionsByBundle('user', 'user');
    $this->assertEqual($form_modes, array('default' => 'Default', 'register' => 'Register'));
  }

}
