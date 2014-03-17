<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldInfoTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\Language;

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

    $field_test_info = $this->getExpectedFieldTypeDefinition();
    $entity_type = \Drupal::service('plugin.manager.field.field_type')->getConfigurableDefinitions();
    foreach ($field_test_info as $t_key => $field_type) {
      foreach ($field_type as $key => $val) {
        $this->assertEqual($entity_type[$t_key][$key], $val, format_string('Field type %t_key key %key is %value', array('%t_key' => $t_key, '%key' => $key, '%value' => print_r($val, TRUE))));
      }
      $this->assertEqual($entity_type[$t_key]['provider'], 'field_test',  'Field type field_test module appears.');
    }

    // Verify that no unexpected instances exist.
    $instances = field_info_instances('entity_test');
    $expected = array();
    $this->assertIdentical($instances, $expected, format_string("field_info_instances('entity_test') returns %expected.", array('%expected' => var_export($expected, TRUE))));
    $instances = field_info_instances('entity_test', 'entity_test');
    $this->assertIdentical($instances, array(), "field_info_instances('entity_test', 'entity_test') returns an empty array.");

    // Create a field, verify it shows up.
    $core_fields = field_info_fields();
    $field = entity_create('field_config', array(
      'name' => drupal_strtolower($this->randomName()),
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ));
    $field->save();
    $fields = field_info_fields();
    $this->assertEqual(count($fields), count($core_fields) + 1, 'One new field exists');
    $this->assertEqual($fields[$field->uuid]->getName(), $field->getName(), 'info fields contains field name');
    $this->assertEqual($fields[$field->uuid]->getType(), $field->getType(), 'info fields contains field type');
    $this->assertEqual($fields[$field->uuid]->module, 'field_test', 'info fields contains field module');
    $settings = array('test_field_setting' => 'dummy test string');
    foreach ($settings as $key => $val) {
      $this->assertEqual($fields[$field->uuid]->getSetting($key), $val, format_string('Field setting %key has correct default value %value', array('%key' => $key, '%value' => $val)));
    }
    $this->assertEqual($fields[$field->uuid]->getCardinality(), 1, 'info fields contains cardinality 1');

    // Create an instance, verify that it shows up
    $instance_definition = array(
      'field_name' => $field->getName(),
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->randomName(),
      'description' => $this->randomName(),
      'weight' => mt_rand(0, 127),
    );
    $instance = entity_create('field_instance_config', $instance_definition);
    $instance->save();

    $entity_type = \Drupal::entityManager()->getDefinition('entity_test');
    $instances = field_info_instances('entity_test', $instance->bundle);
    $this->assertEqual(count($instances), 1, format_string('One instance shows up in info when attached to a bundle on a @label.', array(
      '@label' => $entity_type->getLabel(),
    )));
    $this->assertTrue($instance_definition < $instances[$instance->getName()], 'Instance appears in info correctly');

    // Test a valid entity type but an invalid bundle.
    $instances = field_info_instances('entity_test', 'invalid_bundle');
    $this->assertIdentical($instances, array(), "field_info_instances('entity_test', 'invalid_bundle') returns an empty array.");

    // Test invalid entity type and bundle.
    $instances = field_info_instances('invalid_entity', $instance->bundle);
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
      'name' => 'field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    $field = entity_create('field_config', $field_definition);
    $field->save();

    // Simulate a stored field definition missing a field setting (e.g. a
    // third-party module adding a new field setting has been enabled, and
    // existing fields do not know the setting yet).
    \Drupal::config('field.field.' . $field->id())
      ->set('settings', array())
      ->save();
    field_info_cache_clear();

    // Read the field back.
    $field = field_info_field('entity_test', $field_definition['name']);

    // Check that all expected settings are in place.
    $field_type = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_definition['type']);
    $this->assertEqual($field->settings, $field_type['settings'], 'All expected default field settings are present.');
  }

  /**
   * Test that cached instance definitions are ready for current runtime context.
   */
  function testInstancePrepare() {
    $field_definition = array(
      'name' => 'field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    entity_create('field_config', $field_definition)->save();
    $instance_definition = array(
      'field_name' => $field_definition['name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    $instance = entity_create('field_instance_config', $instance_definition);
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
    $field_type = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_definition['type']);
    $this->assertEqual($instance->settings, $field_type['instance_settings'] , 'All expected instance settings are present.');
  }

  /**
   * Test that instances on disabled entity types are filtered out.
   */
  function testInstanceDisabledEntityType() {
    // Disabling a module invokes user_modules_uninstalled() and calls
    // drupal_flush_all_caches(). Install the necessary schema to support this.
    $this->installSchema('user', array('users_data'));
    $this->installSchema('system', array('router'));

    // For this test the field type and the entity type must be exposed by
    // different modules.
    $field_definition = array(
      'name' => 'field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    entity_create('field_config', $field_definition)->save();
    $instance_definition = array(
      'field_name' => 'field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    entity_create('field_instance_config', $instance_definition)->save();

    $this->assertNotNull(field_info_instance('entity_test', 'field', 'entity_test'), 'Instance is returned on enabled entity types.');
    // Disable comment module. This clears field_info cache.
    module_uninstall(array('entity_test'));
    $this->assertNull(field_info_instance('entity_test', 'field', 'entity_test'), 'No instances are returned on disabled entity types.');
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
        'name' => 'field_1',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      ),
      array(
        'name' => 'field_2',
        'entity_type' => 'entity_test',
        'type' => 'hidden_test_field',
      ),
      array(
        'name' => 'field_2',
        'entity_type' => 'entity_test_cache',
        'type' => 'hidden_test_field',
      ),
    );
    foreach ($fields as $field) {
      entity_create('field_config', $field)->save();
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
      entity_create('field_instance_config', $instance)->save();
    }

    $expected = array(
      'entity_test' => array(
        'field_1' => array(
          'type' => 'test_field',
          'bundles' => array('entity_test', 'test_bundle_2'),
        ),
        'field_2' => array(
          'type' => 'hidden_test_field',
          'bundles' => array('entity_test'),
        ),
      ),
      'entity_test_cache' => array(
        'field_2' => array(
          'type' => 'hidden_test_field',
          'bundles' => array('entity_test')
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
    $info = $this->getExpectedFieldTypeDefinition();
    foreach ($info as $type => $data) {
      $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
      $this->assertIdentical($field_type_manager->getDefaultSettings($type), $data['settings'], format_string("field settings service returns %type's field settings", array('%type' => $type)));
      $this->assertIdentical($field_type_manager->getDefaultInstanceSettings($type), $data['instance_settings'], format_string("field instance settings service returns %type's field instance settings", array('%type' => $type)));
    }
  }

  /**
   * Tests that the field info cache can be built correctly.
   */
  function testFieldInfoCache() {
    // Create a test field and ensure it's in the array returned by
    // field_info_fields().
    $field_name = drupal_strtolower($this->randomName());
    $field = entity_create('field_config', array(
      'name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ));
    $field->save();
    $fields = field_info_fields();
    $this->assertTrue(isset($fields[$field->uuid]), 'The test field is initially found in the array returned by field_info_fields().');

    // Now rebuild the field info cache, and set a variable which will cause
    // the cache to be cleared while it's being rebuilt; see
    // field_test_entity_type_build(). Ensure the test field is still in the returned
    // array.
    field_info_cache_clear();
    \Drupal::state()->set('field_test.clear_info_cache_in_hook_entity_type_build', TRUE);
    $fields = field_info_fields();
    $this->assertTrue(isset($fields[$field->uuid]), 'The test field is found in the array returned by field_info_fields() even if its cache is cleared while being rebuilt.');
  }

  /**
   * Test that the widget definition functions work.
   */
  function testWidgetDefinition() {
    $widget_definition = \Drupal::service('plugin.manager.field.widget')->getDefinition('test_field_widget_multiple');

    // Test if hook_field_widget_info_alter is beïng called.
    $this->assertTrue(in_array('test_field', $widget_definition['field_types']), "The 'test_field_widget_multiple' widget is enabled for the 'test_field' field type in field_test_field_widget_info_alter().");
  }

  /**
   * Returns field info definition.
   */
  protected function getExpectedFieldTypeDefinition() {
    return array(
      'test_field' => array(
        'label' => t('Test field'),
        'description' => t('Dummy field type used for tests.'),
        'settings' => array(
          'test_field_setting' => 'dummy test string',
          'changeable' => 'a changeable field setting',
          'unchangeable' => 'an unchangeable field setting',
        ),
        'instance_settings' => array(
          'test_instance_setting' => 'dummy test string',
          'test_cached_data' => FALSE,
        ),
        'default_widget' => 'test_field_widget',
        'default_formatter' => 'field_test_default',
        'class' => 'Drupal\field_test\Plugin\Field\FieldType\TestItem',
      ),
      'shape' => array(
        'label' => t('Shape'),
        'description' => t('Another dummy field type.'),
        'settings' => array(
          'foreign_key_name' => 'shape',
        ),
        'instance_settings' => array(),
        'default_widget' => 'test_field_widget',
        'default_formatter' => 'field_test_default',
        'class' => 'Drupal\field_test\Plugin\Field\FieldType\ShapeItem',
      ),
      'hidden_test_field' => array(
        'no_ui' => TRUE,
        'label' => t('Hidden from UI test field'),
        'description' => t('Dummy hidden field type used for tests.'),
        'settings' => array(),
        'instance_settings' => array(),
        'default_widget' => 'test_field_widget',
        'default_formatter' => 'field_test_default',
        'class' => 'Drupal\field_test\Plugin\Field\FieldType\HiddenTestItem',
      ),
    );
  }

  /**
   * Tests that the extra fields can be translated.
   */
  function testFieldInfoExtraFieldsTranslation() {
    $this->enableModules(array('language', 'locale'));
    $this->installSchema('locale', array('locales_source', 'locales_target', 'locales_location'));
    foreach (array('en', 'hu') as $id) {
      $language = new Language(array(
        'id' => $id,
      ));
      language_save($language);
    }
    $locale_storage = $this->container->get('locale.storage');

    // Create test source string.
    $en_string = $locale_storage->createString(array(
      'source' => 'User name and password',
      'context' => '',
    ))->save();

    // Create translation for new string and save it.
    $translated_string = $this->randomString();
    $locale_storage->createTranslation(array(
      'lid' => $en_string->lid,
      'language' => 'hu',
      'translation' => $translated_string,
    ))->save();

    // Check that the label is translated.
    \Drupal::translation()->setDefaultLangcode('hu');
    $field_info = \Drupal::service('field.info');
    $user_fields = $field_info->getBundleExtraFields('user', 'user');
    $this->assertEqual($user_fields['form']['account']['label'], $translated_string);
  }

}
