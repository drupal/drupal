<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldInfoTest.
 */

namespace Drupal\field\Tests;

class FieldInfoTest extends FieldTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field info tests',
      'description' => 'Get information about existing fields, instances and bundles.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    parent::setUp('field_test');
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
        $this->assertEqual($info[$t_key][$key], $val, t("Field type $t_key key $key is $val"));
      }
      $this->assertEqual($info[$t_key]['module'], 'field_test',  t("Field type field_test module appears"));
    }

    $formatter_info = field_test_field_formatter_info();
    $info = field_info_formatter_types();
    foreach ($formatter_info as $f_key => $formatter) {
      foreach ($formatter as $key => $val) {
        $this->assertEqual($info[$f_key][$key], $val, t("Formatter type $f_key key $key is $val"));
      }
      $this->assertEqual($info[$f_key]['module'], 'field_test',  t("Formatter type field_test module appears"));
    }

    $widget_info = field_test_field_widget_info();
    $info = field_info_widget_types();
    foreach ($widget_info as $w_key => $widget) {
      foreach ($widget as $key => $val) {
        $this->assertEqual($info[$w_key][$key], $val, t("Widget type $w_key key $key is $val"));
      }
      $this->assertEqual($info[$w_key]['module'], 'field_test',  t("Widget type field_test module appears"));
    }

    $storage_info = field_test_field_storage_info();
    $info = field_info_storage_types();
    foreach ($storage_info as $s_key => $storage) {
      foreach ($storage as $key => $val) {
        $this->assertEqual($info[$s_key][$key], $val, t("Storage type $s_key key $key is $val"));
      }
      $this->assertEqual($info[$s_key]['module'], 'field_test',  t("Storage type field_test module appears"));
    }

    // Verify that no unexpected instances exist.
    $instances = field_info_instances('test_entity');
    $expected = array('test_bundle' => array());
    $this->assertIdentical($instances, $expected, "field_info_instances('test_entity') returns " . var_export($expected, TRUE) . '.');
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
    $this->assertEqual(count($fields), count($core_fields) + 1, t('One new field exists'));
    $this->assertEqual($fields[$field['field_name']]['field_name'], $field['field_name'], t('info fields contains field name'));
    $this->assertEqual($fields[$field['field_name']]['type'], $field['type'], t('info fields contains field type'));
    $this->assertEqual($fields[$field['field_name']]['module'], 'field_test', t('info fields contains field module'));
    $settings = array('test_field_setting' => 'dummy test string');
    foreach ($settings as $key => $val) {
      $this->assertEqual($fields[$field['field_name']]['settings'][$key], $val, t("Field setting $key has correct default value $val"));
    }
    $this->assertEqual($fields[$field['field_name']]['cardinality'], 1, t('info fields contains cardinality 1'));
    $this->assertEqual($fields[$field['field_name']]['active'], 1, t('info fields contains active 1'));

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
    $this->assertEqual(count($instances), 1, t('One instance shows up in info when attached to a bundle.'));
    $this->assertTrue($instance < $instances[$instance['field_name']], t('Instance appears in info correctly'));

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
    $expected = array('user' => array());
    $this->assertIdentical($instances, $expected, "field_info_instances('user') returns " . var_export($expected, TRUE) . '.');
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
    $this->assertIdentical($field['settings'], $field_type['settings'], t('All expected default field settings are present.'));
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
    $this->assertIdentical($instance['settings'], $field_type['instance_settings'] , t('All expected instance settings are present.'));

    // Check that the default widget is used and expected settings are in place.
    $this->assertIdentical($instance['widget']['type'], $field_type['default_widget'], t('Unavailable widget replaced with default widget.'));
    $widget_type = field_info_widget_types($instance['widget']['type']);
    $this->assertIdentical($instance['widget']['settings'], $widget_type['settings'] , t('All expected widget settings are present.'));

    // Check that display settings are set for the 'default' mode.
    $display = $instance['display']['default'];
    $this->assertIdentical($display['type'], $field_type['default_formatter'], t("Formatter is set for the 'default' view mode"));
    $formatter_type = field_info_formatter_types($display['type']);
    $this->assertIdentical($display['settings'], $formatter_type['settings'] , t("Formatter settings are set for the 'default' view mode"));
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
    $this->assertNull(field_info_instance('comment', 'field', 'comment_node_article'), t('No instances are returned on disabled entity types.'));
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
      $this->assertIdentical(field_info_field_settings($type), $data['settings'], "field_info_field_settings returns {$type}'s field settings");
      $this->assertIdentical(field_info_instance_settings($type), $data['instance_settings'], "field_info_field_settings returns {$type}'s field instance settings");
    }

    $info = field_test_field_widget_info();
    foreach ($info as $type => $data) {
      $this->assertIdentical(field_info_widget_settings($type), $data['settings'], "field_info_widget_settings returns {$type}'s widget settings");
    }

    $info = field_test_field_formatter_info();
    foreach ($info as $type => $data) {
      $this->assertIdentical(field_info_formatter_settings($type), $data['settings'], "field_info_formatter_settings returns {$type}'s formatter settings");
    }
  }
}
