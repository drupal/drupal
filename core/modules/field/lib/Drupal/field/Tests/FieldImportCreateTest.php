<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportCreateTest.
 */

namespace Drupal\field\Tests;

use Drupal\field\Field;

/**
 * Tests creating fields and instances as part of config import.
 */
class FieldImportCreateTest extends FieldUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field config create tests',
      'description' => 'Create field and instances during config create method invocation.',
      'group' => 'Field API',
    );
  }

  /**
   * Tests creating fields and instances during default config import.
   */
  function testImportCreateDefault() {
    $field_id = 'field_test_import';
    $instance_id = "entity_test.entity_test.$field_id";
    $field_id_2 = 'field_test_import_2';
    $instance_id_2a = "entity_test.entity_test.$field_id_2";
    $instance_id_2b = "entity_test.entity_test.$field_id_2";

    // Check that the fields and instances do not exist yet.
    $this->assertFalse(entity_load('field_entity', $field_id));
    $this->assertFalse(entity_load('field_instance', $instance_id));
    $this->assertFalse(entity_load('field_entity', $field_id_2));
    $this->assertFalse(entity_load('field_instance', $instance_id_2a));
    $this->assertFalse(entity_load('field_instance', $instance_id_2b));

    // Create a second bundle for the 'Entity test' entity type.
    entity_test_create_bundle('test_bundle');

    // Enable field_test_config module and check that the field and instance
    // shipped in the module's default config were created.
    module_enable(array('field_test_config'));

    // A field with one instance.
    $field = entity_load('field_entity', $field_id);
    $this->assertTrue($field, 'The field was created.');
    $instance = entity_load('field_instance', $instance_id);
    $this->assertTrue($instance, 'The field instance was deleted.');

    // A field with multiple instances.
    $field_2 = entity_load('field_entity', $field_id_2);
    $this->assertTrue($field_2, 'The second field was created.');
    $instance_2a = entity_load('field_instance', $instance_id_2a);
    $this->assertTrue($instance->bundle, 'test_bundle', 'The second field instance was created on bundle test_bundle.');
    $instance_2b = entity_load('field_instance', $instance_id_2b);
    $this->assertTrue($instance->bundle, 'test_bundle_2', 'The second field instance was created on bundle test_bundle_2.');

    // Tests field info contains the right data.
    $instances = Field::fieldInfo()->getInstances('entity_test');
    $this->assertEqual(count($instances['entity_test']), 2);
    $this->assertTrue(isset($instances['entity_test']['field_test_import']));
    $this->assertTrue(isset($instances['entity_test']['field_test_import_2']));
    $this->assertEqual(count($instances['test_bundle']), 1);
    $this->assertTrue(isset($instances['test_bundle']['field_test_import_2']));
  }

  /**
   * Tests creating fields and instances during config import.
   */
  function testImportCreate() {
    // One field with one field instance.
    $field_id = 'field_test_import_staging';
    $instance_id = "entity_test.entity_test.$field_id";
    $field_config_name = "field.field.$field_id";
    $instance_config_name = "field.instance.$instance_id";

    // One field with two field instances.
    $field_id_2 = 'field_test_import_staging_2';
    $instance_id_2a = "entity_test.test_bundle.$field_id_2";
    $instance_id_2b = "entity_test.test_bundle_2.$field_id_2";
    $field_config_name_2 = "field.field.$field_id_2";
    $instance_config_name_2a = "field.instance.$instance_id_2a";
    $instance_config_name_2b = "field.instance.$instance_id_2b";

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    // Add the new files to the staging directory.
    $src_dir = drupal_get_path('module', 'field_test_config') . '/staging';
    $this->assertTrue(file_unmanaged_copy("$src_dir/$field_config_name.yml", "public://config_staging/$field_config_name.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$instance_config_name.yml", "public://config_staging/$instance_config_name.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$field_config_name_2.yml", "public://config_staging/$field_config_name_2.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$instance_config_name_2a.yml", "public://config_staging/$instance_config_name_2a.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$instance_config_name_2b.yml", "public://config_staging/$instance_config_name_2b.yml"));

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the field and instance were created.
    $field = entity_load('field_entity', $field_id);
    $this->assertTrue($field, 'Test import field from staging exists');
    $instance = entity_load('field_instance', $instance_id);
    $this->assertTrue($instance, 'Test import field instance from staging exists');
    $field = entity_load('field_entity', $field_id_2);
    $this->assertTrue($field, 'Test import field 2 from staging exists');
    $instance = entity_load('field_instance', $instance_id_2a);
    $this->assertTrue($instance, 'Test import field instance 2a from staging exists');
    $instance = entity_load('field_instance', $instance_id_2b);
    $this->assertTrue($instance, 'Test import field instance 2b from staging exists');
  }
}

