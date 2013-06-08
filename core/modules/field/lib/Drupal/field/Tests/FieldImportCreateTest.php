<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportCreateTest.
 */

namespace Drupal\field\Tests;

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
    $instance_id = "test_entity.test_bundle.$field_id";

    // Check that the field and instance do not exist yet.
    $this->assertFalse(entity_load('field_entity', $field_id));
    $this->assertFalse(entity_load('field_instance', $instance_id));

    // Enable field_test_config module and check that the field and instance
    // shipped in the module's default config were created.
    module_enable(array('field_test_config'));
    $field = entity_load('field_entity', $field_id);
    $this->assertTrue($field, 'The field was created.');
    $instance = entity_load('field_instance', $instance_id);
    $this->assertTrue($instance, 'The field instance was deleted.');
  }

  /**
   * Tests creating fields and instances during config import.
   */
  function testImportCreate() {
    $field_id = 'field_test_import_staging';
    $instance_id = "test_entity.test_bundle.$field_id";
    $field_config_name = "field.field.$field_id";
    $instance_config_name = "field.instance.$instance_id";

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    // Add the new files to the staging directory.
    $src_dir = drupal_get_path('module', 'field_test_config') . '/staging';
    $this->assertTrue(file_unmanaged_copy("$src_dir/$field_config_name.yml", "public://config_staging/$field_config_name.yml"));
    $this->assertTrue(file_unmanaged_copy("$src_dir/$instance_config_name.yml", "public://config_staging/$instance_config_name.yml"));

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the field and instance were created.
    $field = entity_load('field_entity', $field_id);
    $this->assertTrue($field, 'Test import field from staging exists');
    $instance = entity_load('field_instance', $instance_id);
    $this->assertTrue($instance, 'Test import field instance from staging exists');
  }
}

