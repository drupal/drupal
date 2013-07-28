<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldInfoTest.
 */

namespace Drupal\field\Tests;

class FieldInfoTest extends FieldUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field info tests',
      'description' => 'Get information about existing fields, instances and bundles.',
      'group' => 'Field API',
    );
  }

  /**
   * Test that field types and field definitions are correctly cached.
   */
  function testFieldInfo() {
    // Test that field_test module's fields, widgets, and formatters show up.

    $field_test_info = field_test_field_info();
    $info = field_info_field_types();
    foreach ($field_test_info as $t_key => $field_type) {
      foreach ($field_type as $key => $val) {
        $this->assertEqual($info[$t_key][$key], $val, format_string('Field type %t_key key %key is %value', array('%t_key' => $t_key, '%key' => $key, '%value' => print_r($val, TRUE))));
      }
      $this->assertEqual($info[$t_key]['provider'], 'field_test',  'Field type field_test module appears.');
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
    $instances = field_info_instances('entity_test');
    $expected = array();
    $this->assertIdentical($instances, $expected, format_string("field_info_instances('entity_test') returns %expected.", array('%expected' => var_export($expected, TRUE))));
    $instances = field_info_instances('entity_test', 'entity_test');
    $this->assertIdentical($instances, array(), "field_info_instances('entity_test', 'entity_test') returns an empty array.");

    // Create a field, verify it shows up.
    $core_fields = field_info_fields();
    $field = entity_create('field_entity', array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'test_field',
    ));
    $field->save();
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
    $this->assertEqual($fields[$field['field_name']]['active'], TRUE, 'info fields contains active 1');

    // Create an instance, verify that it shows up
    $instance_definition = array(
      'field_name' => $field['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->randomName(),
      'description' => $this->randomName(),
      'weight' => mt_rand(0, 127),
    );
    $instance = entity_create('field_instance', $instance_definition);
    $instance->save();

    $info = entity_get_info('entity_test');
    $instances = field_info_instances('entity_test', $instance['bundle']);
    $this->assertEqual(count($instances), 1, format_string('One instance shows up in info when attached to a bundle on a @label.', array(
      '@label' => $info['label']
    )));
    $this->assertTrue($instance_definition < $instances[$instance['field_name']], 'Instance appears in info correctly');

    // Test a valid entity type but an invalid bundle.
    $instances = field_info_instances('entity_test', 'invalid_bundle');
    $this->assertIdentical($instances, array(), "field_info_instances('entity_test', 'invalid_bundle') returns an empty array.");

    // Test invalid entity type and bundle.
    $instances = field_info_instances('invalid_entity', $instance['bundle']);
    $this->assertIdentical($instances, array(), "field_info_instances('invalid_entity', 'entity_test') returns an empty array.");

    // Test invalid entity type, no bundle provided.
    $instances = field_info_instances('invalid_entity');
    $this->assertIdentical($instances, array(), "field_info_instances('invalid_entity') returns an empty array.");

    // Test with an entity type that has no bundles.
    $instances = field_info_instances('user');
    $expected = array();
    $this->assertIdentical($instances, $expected, format_string("field_info_instances('user') returns %expected.", array('%expected' => var_export($expected, TRUE))));
    $instances = field_info_instances('user', 'user');
    $this->assertIdentical($instances, array(), "field_info_instances('user', 'user') returns an empty array.");

    // Test that querying for invalid entity types does not add entries in the
    // list returned by field_info_instances().
    field_info_cache_clear();
    field_info_instances('invalid_entity', 'invalid_bundle');
    // Simulate new request by clearing static caches.
    drupal_static_reset();
    field_info_instances('invalid_entity', 'invalid_bundle');
    $instances = field_info_instances();
    $this->assertFalse(isset($instances['invalid_entity']), 'field_info_instances() does not contain entries for the invalid entity type that was queried before');
  }

  /**
   * Test that cached field definitions are ready for current runtime context.
   */
  function testFieldPrepare() {
    $field_definition = array(
      'field_name' => 'field',
      'type' => 'test_field',
    );
    $field = entity_create('field_entity', $field_definition);
    $field->save();

    // Simulate a stored field definition missing a field setting (e.g. a
    // third-party module adding a new field setting has been enabled, and
    // existing fields do not know the setting yet).
    \Drupal::config('field.field.' . $field->id())
      ->set('settings', array())
      ->save();
    field_info_cache_clear();

    // Read the field back.
    $field = field_info_field($field_definition['field_name']);

    // Check that all expected settings are in place.
    $field_type = field_info_field_types($field_definition['type']);
    $this->assertEqual($field['settings'], $field_type['settings'], 'All expected default field settings are present.');
  }

  /**
   * Test that cached instance definitions are ready for current runtime context.
   */
  function testInstancePrepare() {
    $field_definition = array(
      'field_name' => 'field',
      'type' => 'test_field',
    );
    entity_create('field_entity', $field_definition)->save();
    $instance_definition = array(
      'field_name' => $field_definition['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    $instance = entity_create('field_instance', $instance_definition);
    $instance->save();

    // Simulate a stored instance definition missing various settings (e.g. a
    // third-party module adding instance settings has been enabled, but
    // existing instances do not know the new settings).
    \Drupal::config('field.instance.' . $instance->id())
      ->set('settings', array())
      ->save();
    field_info_cache_clear();

    // Read the instance back.
    $instance = field_info_instance($instance_definition['entity_type'], $instance_definition['field_name'], $instance_definition['bundle']);

    // Check that all expected instance settings are in place.
    $field_type = field_info_field_types($field_definition['type']);
    $this->assertEqual($instance['settings'], $field_type['instance_settings'] , 'All expected instance settings are present.');
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
    entity_create('field_entity', $field_definition)->save();
    $instance_definition = array(
      'field_name' => 'field',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_article',
    );
    entity_create('field_instance', $instance_definition)->save();

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

    // Create a new bundle for 'entity_test' entity type.
    entity_test_create_bundle('test_bundle_2');

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
      entity_create('field_entity', $field)->save();
    }

    // Create a couple instances.
    $instances = array(
      array(
        'field_name' => 'field_1',
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
      ),
      array(
        'field_name' => 'field_1',
        'entity_type' => 'entity_test',
        'bundle' => 'test_bundle_2',
      ),
      array(
        'field_name' => 'field_2',
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
      ),
      array(
        'field_name' => 'field_2',
        'entity_type' => 'entity_test_cache',
        'bundle' => 'entity_test',
      ),
    );
    foreach ($instances as $instance) {
      entity_create('field_instance', $instance)->save();
    }

    $expected = array(
      'field_1' => array(
        'type' => 'test_field',
        'bundles' => array(
          'entity_test' => array('entity_test', 'test_bundle_2'),
        ),
      ),
      'field_2' => array(
        'type' => 'hidden_test_field',
        'bundles' => array(
          'entity_test' => array('entity_test'),
          'entity_test_cache' => array('entity_test'),
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

  /**
   * Tests that the field info cache can be built correctly.
   */
  function testFieldInfoCache() {
    // Create a test field and ensure it's in the array returned by
    // field_info_fields().
    $field_name = drupal_strtolower($this->randomName());
    $field = entity_create('field_entity', array(
      'field_name' => $field_name,
      'type' => 'test_field',
    ));
    $field->save();
    $fields = field_info_fields();
    $this->assertTrue(isset($fields[$field_name]), 'The test field is initially found in the array returned by field_info_fields().');

    // Now rebuild the field info cache, and set a variable which will cause
    // the cache to be cleared while it's being rebuilt; see
    // field_test_entity_info(). Ensure the test field is still in the returned
    // array.
    field_info_cache_clear();
    \Drupal::state()->set('field_test.clear_info_cache_in_hook_entity_info', TRUE);
    $fields = field_info_fields();
    $this->assertTrue(isset($fields[$field_name]), 'The test field is found in the array returned by field_info_fields() even if its cache is cleared while being rebuilt.');
  }

  /**
   * Test that the widget definition functions work.
   */
  function testWidgetDefinition() {
    $widget_definition = field_info_widget_types('test_field_widget_multiple');

    // Test if hook_field_widget_info_alter is beïng called.
    $this->assertTrue(in_array('test_field', $widget_definition['field_types']), "The 'test_field_widget_multiple' widget is enabled for the 'test_field' field type in field_test_field_widget_info_alter().");
  }

}
