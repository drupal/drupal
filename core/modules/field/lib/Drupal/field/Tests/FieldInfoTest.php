<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldInfoTest.
 */

namespace Drupal\field\Tests;

class FieldInfoTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  public static function getInfo() {
    return array(
      'name' => 'Field info tests',
      'description' => 'Get information about existing fields, instances and bundles.',
      'group' => 'Field API',
    );
  }

  /**
   * Test that field types and field definitions are correcly cached.
   */
  function testFieldInfo() {
    // Test that field_test module's fields, widgets, and formatters show up.

    $field_test_info = field_test_field_info();
    // We need to account for the existence of user_field_info_alter().
    foreach (array_keys($field_test_info) as $name) {
      $field_test_info[$name]['instance_settings']['user_register_form'] = FALSE;
    }
    $info = field_info_field_types();
    foreach ($field_test_info as $t_key => $field_type) {
      foreach ($field_type as $key => $val) {
        $this->assertEqual($info[$t_key][$key], $val, format_string('Field type %t_key key %key is %value', array('%t_key' => $t_key, '%key' => $key, '%value' => print_r($val, TRUE))));
      }
      $this->assertEqual($info[$t_key]['module'], 'field_test',  'Field type field_test module appears.');
    }

    $storage_info = field_test_field_storage_info();
    $info = field_info_storage_types();
    foreach ($storage_info as $s_key => $storage) {
      foreach ($storage as $key => $val) {
        $this->assertEqual($info[$s_key][$key], $val, format_string('Storage type %s_key key %key is %value', array('%s_key' => $s_key, '%key' => $key, '%value' => print_r($val, TRUE))));
      }
      $this->assertEqual($info[$s_key]['module'], 'field_test',  'Storage type field_test module appears.');
    }

    // Verify that no unexpected instances exist.
    $instances = field_info_instances('test_entity');
    $expected = array();
    $this->assertIdentical($instances, $expected, format_string("field_info_instances('test_entity') returns %expected.", array('%expected' => var_export($expected, TRUE))));
    $instances = field_info_instances('test_entity', 'test_bundle');
    $this->assertIdentical($instances, array(), "field_info_instances('test_entity', 'test_bundle') returns an empty array.");

    // Create a field, verify it shows up.
    $core_fields = field_info_fields();
    $field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'test_field',
    );
    field_create_field($field);
    $fields = field_info_fields();
    $this->assertEqual(count($fields), count($core_fields) + 1, 'One new field exists');
    $this->assertEqual($fields[$field['field_name']]['field_name'], $field['field_name'], 'info fields contains field name');
    $this->assertEqual($fields[$field['field_name']]['type'], $field['type'], 'info fields contains field type');
    $this->assertEqual($fields[$field['field_name']]['module'], 'field_test', 'info fields contains field module');
    $settings = array('test_field_setting' => 'dummy test string');
    foreach ($settings as $key => $val) {
      $this->assertEqual($fields[$field['field_name']]['settings'][$key], $val, format_string('Field setting %key has correct default value %value', array('%key' => $key, '%value' => $val)));
    }
    $this->assertEqual($fields[$field['field_name']]['cardinality'], 1, 'info fields contains cardinality 1');
    $this->assertEqual($fields[$field['field_name']]['active'], 1, 'info fields contains active 1');

    // Create an instance, verify that it shows up
    $instance = array(
      'field_name' => $field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => $this->randomName(),
      'description' => $this->randomName(),
      'weight' => mt_rand(0, 127),
      // test_field has no instance settings
      'widget' => array(
        'type' => 'test_field_widget',
        'settings' => array(
          'test_setting' => 999)));
    field_create_instance($instance);

    $instances = field_info_instances('test_entity', $instance['bundle']);
    $this->assertEqual(count($instances), 1, 'One instance shows up in info when attached to a bundle.');
    $this->assertTrue($instance < $instances[$instance['field_name']], 'Instance appears in info correctly');

    // Test a valid entity type but an invalid bundle.
    $instances = field_info_instances('test_entity', 'invalid_bundle');
    $this->assertIdentical($instances, array(), "field_info_instances('test_entity', 'invalid_bundle') returns an empty array.");

    // Test invalid entity type and bundle.
    $instances = field_info_instances('invalid_entity', $instance['bundle']);
    $this->assertIdentical($instances, array(), "field_info_instances('invalid_entity', 'test_bundle') returns an empty array.");

    // Test invalid entity type, no bundle provided.
    $instances = field_info_instances('invalid_entity');
    $this->assertIdentical($instances, array(), "field_info_instances('invalid_entity') returns an empty array.");

    // Test with an entity type that has no bundles.
    $instances = field_info_instances('user');
    $expected = array();
    $this->assertIdentical($instances, $expected, format_string("field_info_instances('user') returns %expected.", array('%expected' => var_export($expected, TRUE))));
    $instances = field_info_instances('user', 'user');
    $this->assertIdentical($instances, array(), "field_info_instances('user', 'user') returns an empty array.");
  }

  /**
   * Test that cached field definitions are ready for current runtime context.
   */
  function testFieldPrepare() {
    $field_definition = array(
      'field_name' => 'field',
      'type' => 'test_field',
    );
    field_create_field($field_definition);

    // Simulate a stored field definition missing a field setting (e.g. a
    // third-party module adding a new field setting has been enabled, and
    // existing fields do not know the setting yet).
    $data = db_query('SELECT data FROM {field_config} WHERE field_name = :field_name', array(':field_name' => $field_definition['field_name']))->fetchField();
    $data = unserialize($data);
    $data['settings'] = array();
    db_update('field_config')
      ->fields(array('data' => serialize($data)))
      ->condition('field_name', $field_definition['field_name'])
      ->execute();

    field_cache_clear();

    // Read the field back.
    $field = field_info_field($field_definition['field_name']);

    // Check that all expected settings are in place.
    $field_type = field_info_field_types($field_definition['type']);
    $this->assertIdentical($field['settings'], $field_type['settings'], 'All expected default field settings are present.');
  }

  /**
   * Test that cached instance definitions are ready for current runtime context.
   */
  function testInstancePrepare() {
    $field_definition = array(
      'field_name' => 'field',
      'type' => 'test_field',
    );
    field_create_field($field_definition);
    $instance_definition = array(
      'field_name' => $field_definition['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
    );
    field_create_instance($instance_definition);

    // Simulate a stored instance definition missing various settings (e.g. a
    // third-party module adding instance, widget or display settings has been
    // enabled, but existing instances do not know the new settings).
    $data = db_query('SELECT data FROM {field_config_instance} WHERE field_name = :field_name AND bundle = :bundle', array(':field_name' => $instance_definition['field_name'], ':bundle' => $instance_definition['bundle']))->fetchField();
    $data = unserialize($data);
    $data['settings'] = array();
    $data['widget']['settings'] = 'unavailable_widget';
    $data['widget']['settings'] = array();
    $data['display']['default']['type'] = 'unavailable_formatter';
    $data['display']['default']['settings'] = array();
    db_update('field_config_instance')
      ->fields(array('data' => serialize($data)))
      ->condition('field_name', $instance_definition['field_name'])
      ->condition('bundle', $instance_definition['bundle'])
      ->execute();

    field_cache_clear();

    // Read the instance back.
    $instance = field_info_instance($instance_definition['entity_type'], $instance_definition['field_name'], $instance_definition['bundle']);

    // Check that all expected instance settings are in place.
    $field_type = field_info_field_types($field_definition['type']);
    $this->assertIdentical($instance['settings'], $field_type['instance_settings'] , 'All expected instance settings are present.');

    // Check that the default widget is used and expected settings are in place.
    $widget = $instance->getWidget();
    $this->assertIdentical($widget->getPluginId(), $field_type['default_widget'], 'Unavailable widget replaced with default widget.');
    $widget_type = $widget->getDefinition();
    $this->assertIdentical($widget->getSettings(), $widget_type['settings'] , 'All expected widget settings are present.');

    // Check that display settings are set for the 'default' mode.
    $formatter = $instance->getFormatter('default');
    $this->assertIdentical($formatter->getPluginId(), $field_type['default_formatter'], "Formatter is set for the 'default' view mode");
    $formatter_type = $formatter->getDefinition();
    $this->assertIdentical($formatter->getSettings(), $formatter_type['settings'] , "Formatter settings are set for the 'default' view mode");

  }

  /**
   * Test that instances on disabled entity types are filtered out.
   */
  function testInstanceDisabledEntityType() {
    // For this test the field type and the entity type must be exposed by
    // different modules.
    $field_definition = array(
      'field_name' => 'field',
      'type' => 'test_field',
    );
    field_create_field($field_definition);
    $instance_definition = array(
      'field_name' => 'field',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_article',
    );
    field_create_instance($instance_definition);

    // Disable coment module. This clears field_info cache.
    module_disable(array('comment'));
    $this->assertNull(field_info_instance('comment', 'field', 'comment_node_article'), 'No instances are returned on disabled entity types.');
  }

  /**
   * Test field_info_field_map().
   */
  function testFieldMap() {
    // We will overlook fields created by the 'standard' installation profile.
    $exclude = field_info_field_map();

    // Create a new bundle for 'test_entity' entity type.
    field_test_create_bundle('test_bundle_2');

    // Create a couple fields.
    $fields  = array(
      array(
        'field_name' => 'field_1',
        'type' => 'test_field',
      ),
      array(
        'field_name' => 'field_2',
        'type' => 'hidden_test_field',
      ),
    );
    foreach ($fields as $field) {
      field_create_field($field);
    }

    // Create a couple instances.
    $instances = array(
      array(
        'field_name' => 'field_1',
        'entity_type' => 'test_entity',
        'bundle' => 'test_bundle',
      ),
      array(
        'field_name' => 'field_1',
        'entity_type' => 'test_entity',
        'bundle' => 'test_bundle_2',
      ),
      array(
        'field_name' => 'field_2',
        'entity_type' => 'test_entity',
        'bundle' => 'test_bundle',
      ),
      array(
        'field_name' => 'field_2',
        'entity_type' => 'test_cacheable_entity',
        'bundle' => 'test_bundle',
      ),
    );
    foreach ($instances as $instance) {
      field_create_instance($instance);
    }

    $expected = array(
      'field_1' => array(
        'type' => 'test_field',
        'bundles' => array(
          'test_entity' => array('test_bundle', 'test_bundle_2'),
        ),
      ),
      'field_2' => array(
        'type' => 'hidden_test_field',
        'bundles' => array(
          'test_entity' => array('test_bundle'),
          'test_cacheable_entity' => array('test_bundle'),
        ),
      ),
    );

    // Check that the field map is correct.
    $map = field_info_field_map();
    $map = array_diff_key($map, $exclude);
    $this->assertEqual($map, $expected);
  }


  /**
   * Test that the field_info settings convenience functions work.
   */
  function testSettingsInfo() {
    $info = field_test_field_info();
    // We need to account for the existence of user_field_info_alter().
    foreach (array_keys($info) as $name) {
      $info[$name]['instance_settings']['user_register_form'] = FALSE;
    }
    foreach ($info as $type => $data) {
      $this->assertIdentical(field_info_field_settings($type), $data['settings'], format_string("field_info_field_settings returns %type's field settings", array('%type' => $type)));
      $this->assertIdentical(field_info_instance_settings($type), $data['instance_settings'], format_string("field_info_field_settings returns %type's field instance settings", array('%type' => $type)));
    }

    foreach (array('test_field_widget', 'test_field_widget_multiple') as $type) {
      $info = field_info_widget_types($type);
      $this->assertIdentical(field_info_widget_settings($type), $info['settings'], format_string("field_info_widget_settings returns %type's widget settings", array('%type' => $type)));
    }

    foreach (array('field_test_default', 'field_test_multiple', 'field_test_with_prepare_view') as $type) {
      $info = field_info_formatter_types($type);
      $this->assertIdentical(field_info_formatter_settings($type), $info['settings'], format_string("field_info_formatter_settings returns %type's formatter settings", array('%type' => $type)));
    }
  }
}
