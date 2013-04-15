<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportChangeTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests updating fields and instances as part of config import.
 */
class FieldImportChangeTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test_config');

  public static function getInfo() {
    return array(
      'name' => 'Field config change tests',
      'description' => 'Update field and instances during config change method invocation.',
      'group' => 'Field API',
    );
  }

  /**
   * Tests importing an updated field instance.
   */
  function testImportChange() {
    $field_id = 'field_test_import';
    $instance_id = "test_entity.test_bundle.$field_id";
    $instance_config_name = "field.instance.$instance_id";

    // Import default config.
    $this->installConfig(array('field_test_config'));

    // Simulate config data to import:
    // - the current manifest for field instances,
    // - a modified version (modified label) of the instance config.
    $manifest_name = 'manifest.field.instance';
    $active = $this->container->get('config.storage');
    $manifest = $active->read($manifest_name);
    $instance = $active->read($instance_config_name);
    $new_label = 'Test update import field';
    $instance['label'] = $new_label;

    // Save as files in the the staging directory.
    $staging = $this->container->get('config.storage.staging');
    $staging->write($manifest_name, $manifest);
    $staging->write($instance_config_name, $instance);

    // Import the content of the staging directory.
    config_import();

    // Check that the updated config was correctly imported.
    $instance = entity_load('field_instance', $instance_id);
    $this->assertEqual($instance['label'], $new_label, 'Instance label updated');
  }

}
