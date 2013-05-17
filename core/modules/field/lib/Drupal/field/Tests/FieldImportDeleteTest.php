<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportDeleteTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests deleting fields and instances as part of config import.
 */
class FieldImportDeleteTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test_config');

  public static function getInfo() {
    return array(
      'name' => 'Field config delete tests',
      'description' => 'Delete field and instances during config delete method invocation.',
      'group' => 'Field API',
    );
  }

  /**
   * Tests deleting fields and instances as part of config import.
   */
  function testImportDelete() {
    $field_id = 'field_test_import';
    $instance_id = "test_entity.test_bundle.$field_id";
    $field_config_name = "field.field.$field_id";
    $instance_config_name = "field.instance.$instance_id";

    // Import default config.
    $this->installConfig(array('field_test_config'));

    // Check that the config was correctly imported.
    $field = entity_load('field_entity', $field_id);
    $this->assertTrue($field, 'The field was created.');
    $instance = entity_load('field_instance', $instance_id);
    $this->assertTrue($instance, 'The field instance was created.');

    $field_uuid = $field->uuid;

    // Simulate config data to import:
    // - the current manifest for fields, without the entry for the field we
    //   remove,
    // - the current manifest for instances, without the entry for the instance
    //   we remove.
    $field_manifest_name = 'manifest.field.field';
    $instance_manifest_name = 'manifest.field.instance';
    $active = $this->container->get('config.storage');
    $field_manifest = $active->read($field_manifest_name);
    unset($field_manifest[$field_id]);
    $instance_manifest = $active->read($instance_manifest_name);
    unset($instance_manifest[$instance_id]);

    // Save as files in the the staging directory.
    $staging = $this->container->get('config.storage.staging');
    $staging->write($field_manifest_name, $field_manifest);
    $staging->write($instance_manifest_name, $instance_manifest);

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the field and instance are gone.
    $field = entity_load('field_entity', $field_id, TRUE);
    $this->assertFalse($field, 'The field was deleted.');
    $instance = entity_load('field_instance', $instance_id, TRUE);
    $this->assertFalse($instance, 'The field instance was deleted.');

    // Check that all config files are gone.
    $active = $this->container->get('config.storage');
    $this->assertIdentical($active->listAll($field_config_name), array());
    $this->assertIdentical($active->listAll($instance_config_name), array());

    // Check that the field definition is preserved in state.
    $deleted_fields = \Drupal::state()->get('field.field.deleted') ?: array();
    $this->assertTrue(isset($deleted_fields[$field_uuid]));

    // Purge field data, and check that the field definition has been completely
    // removed once the data is purged.
    field_purge_batch(10);
    $deleted_fields = \Drupal::state()->get('field.field.deleted') ?: array();
    $this->assertTrue(empty($deleted_fields), 'Fields are deleted');
  }

}
