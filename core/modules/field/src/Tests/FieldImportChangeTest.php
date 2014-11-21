<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportChangeTest.
 */

namespace Drupal\field\Tests;

use Drupal\field\Entity\FieldConfig;

/**
 * Update field storage and fields during config change method invocation.
 *
 * @group field
 */
class FieldImportChangeTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test_config');

  /**
   * Tests importing an updated field.
   */
  function testImportChange() {
    $field_storage_id = 'field_test_import';
    $field_id = "entity_test.entity_test.$field_storage_id";
    $field_config_name = "field.field.$field_id";

    // Import default config.
    $this->installConfig(array('field_test_config'));
    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    // Save as files in the the staging directory.
    $field = $active->read($field_config_name);
    $new_label = 'Test update import field';
    $field['label'] = $new_label;
    $staging->write($field_config_name, $field);

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the updated config was correctly imported.
    $field = FieldConfig::load($field_id);
    $this->assertEqual($field->getLabel(), $new_label, 'field label updated');
  }
}

