<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportCreateTest.
 */

namespace Drupal\field\Tests;

/**
 * Create field and instances during config create method invocation.
 *
 * @group field
 */
class FieldImportCreateTest extends FieldUnitTestBase {

  /**
   * Tests creating fields and instances during default config import.
   */
  function testImportCreateDefault() {
    $field_name = 'field_test_import';
    $field_id = "entity_test.$field_name";
    $instance_id = "entity_test.entity_test.$field_name";
    $field_name_2 = 'field_test_import_2';
    $field_id_2 = "entity_test.$field_name_2";
    $instance_id_2a = "entity_test.entity_test.$field_name_2";
    $instance_id_2b = "entity_test.test_bundle.$field_name_2";

    // Check that the fields and instances do not exist yet.
    $this->assertFalse(entity_load('field_storage_config', $field_id));
    $this->assertFalse(entity_load('field_instance_config', $instance_id));
    $this->assertFalse(entity_load('field_storage_config', $field_id_2));
    $this->assertFalse(entity_load('field_instance_config', $instance_id_2a));
    $this->assertFalse(entity_load('field_instance_config', $instance_id_2b));

    // Create a second bundle for the 'Entity test' entity type.
    entity_test_create_bundle('test_bundle');

    // Enable field_test_config module and check that the field and instance
    // shipped in the module's default config were created.
    \Drupal::moduleHandler()->install(array('field_test_config'));

    // A field with one instance.
    $field_storage = entity_load('field_storage_config', $field_id);
    $this->assertTrue($field_storage, 'The field was created.');
    $instance = entity_load('field_instance_config', $instance_id);
    $this->assertTrue($instance, 'The field instance was deleted.');

    // A field with multiple instances.
    $field_storage_2 = entity_load('field_storage_config', $field_id_2);
    $this->assertTrue($field_storage_2, 'The second field was created.');
    $this->assertTrue($instance->bundle, 'test_bundle', 'The second field instance was created on bundle test_bundle.');
    $this->assertTrue($instance->bundle, 'test_bundle_2', 'The second field instance was created on bundle test_bundle_2.');

    // Tests field instances.
    $ids = \Drupal::entityQuery('field_instance_config')
      ->condition('entity_type', 'entity_test')
      ->condition('bundle', 'entity_test')
      ->execute();
    $this->assertEqual(count($ids), 2);
    $this->assertTrue(isset($ids['entity_test.entity_test.field_test_import']));
    $this->assertTrue(isset($ids['entity_test.entity_test.field_test_import_2']));
    $ids = \Drupal::entityQuery('field_instance_config')
      ->condition('entity_type', 'entity_test')
      ->condition('bundle', 'test_bundle')
      ->execute();
    $this->assertEqual(count($ids), 1);
    $this->assertTrue(isset($ids['entity_test.test_bundle.field_test_import_2']));
  }

  /**
   * Tests creating fields and instances during config import.
   */
  function testImportCreate() {
    // One field with one field instance.
    $field_name = 'field_test_import_staging';
    $field_id = "entity_test.$field_name";
    $instance_id = "entity_test.entity_test.$field_name";
    $field_storage_config_name = "field.storage.$field_id";
    $instance_config_name = "field.instance.$instance_id";

    // One field with two field instances.
    $field_name_2 = 'field_test_import_staging_2';
    $field_id_2 = "entity_test.$field_name_2";
    $instance_id_2a = "entity_test.test_bundle.$field_name_2";
    $instance_id_2b = "entity_test.test_bundle_2.$field_name_2";
    $field_storage_config_name_2 = "field.storage.$field_id_2";
    $instance_config_name_2a = "field.instance.$instance_id_2a";
    $instance_config_name_2b = "field.instance.$instance_id_2b";

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    // Add the new files to the staging directory.
    $src_dir = drupal_get_path('module', 'field_test_config') . '/staging';
    $target_dir = $this->configDirectories[CONFIG_STAGING_DIRECTORY];
    $this->assertTrue(file_unmanaged_copy("$src_dir/$field_storage_config_name.yml", "$target_dir/$field_storage_config_name.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$instance_config_name.yml", "$target_dir/$instance_config_name.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$field_storage_config_name_2.yml", "$target_dir/$field_storage_config_name_2.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$instance_config_name_2a.yml", "$target_dir/$instance_config_name_2a.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$instance_config_name_2b.yml", "$target_dir/$instance_config_name_2b.yml"));

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the field and instance were created.
    $field_storage = entity_load('field_storage_config', $field_id);
    $this->assertTrue($field_storage, 'Test import field from staging exists');
    $instance = entity_load('field_instance_config', $instance_id);
    $this->assertTrue($instance, 'Test import field instance from staging exists');
    $field_storage = entity_load('field_storage_config', $field_id_2);
    $this->assertTrue($field_storage, 'Test import field 2 from staging exists');
    $instance = entity_load('field_instance_config', $instance_id_2a);
    $this->assertTrue($instance, 'Test import field instance 2a from staging exists');
    $instance = entity_load('field_instance_config', $instance_id_2b);
    $this->assertTrue($instance, 'Test import field instance 2b from staging exists');
  }
}

