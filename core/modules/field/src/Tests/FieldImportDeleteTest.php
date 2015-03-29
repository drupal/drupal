<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportDeleteTest.
 */

namespace Drupal\field\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Delete field storages and fields during config delete method invocation.
 *
 * @group field
 */
class FieldImportDeleteTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * The default configuration provided by field_test_config is imported by
   * \Drupal\field\Tests\FieldUnitTestBase::setUp() when it installs field
   * configuration.
   *
   * @var array
   */
  public static $modules = array('field_test_config');

  /**
   * Tests deleting field storages and fields as part of config import.
   */
  public function testImportDelete() {
    $this->installConfig(['field_test_config']);
    // At this point there are 5 field configuration objects in the active
    // storage.
    // - field.storage.entity_test.field_test_import
    // - field.storage.entity_test.field_test_import_2
    // - field.field.entity_test.entity_test.field_test_import
    // - field.field.entity_test.entity_test.field_test_import_2
    // - field.field.entity_test.test_bundle.field_test_import_2

    $field_name = 'field_test_import';
    $field_storage_id = "entity_test.$field_name";
    $field_name_2 = 'field_test_import_2';
    $field_storage_id_2 = "entity_test.$field_name_2";
    $field_id = "entity_test.entity_test.$field_name";
    $field_id_2a = "entity_test.entity_test.$field_name_2";
    $field_id_2b = "entity_test.test_bundle.$field_name_2";
    $field_storage_config_name = "field.storage.$field_storage_id";
    $field_storage_config_name_2 = "field.storage.$field_storage_id_2";
    $field_config_name = "field.field.$field_id";
    $field_config_name_2a = "field.field.$field_id_2a";
    $field_config_name_2b = "field.field.$field_id_2b";

    // Create a second bundle for the 'Entity test' entity type.
    entity_test_create_bundle('test_bundle');

    // Get the uuid's for the field storages.
    $field_storage_uuid = FieldStorageConfig::load($field_storage_id)->uuid();
    $field_storage_uuid_2 = FieldStorageConfig::load($field_storage_id_2)->uuid();

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);
    $this->assertTrue($staging->delete($field_storage_config_name), SafeMarkup::format('Deleted field storage: !field_storage', array('!field_storage' => $field_storage_config_name)));
    $this->assertTrue($staging->delete($field_storage_config_name_2), SafeMarkup::format('Deleted field storage: !field_storage', array('!field_storage' => $field_storage_config_name_2)));
    $this->assertTrue($staging->delete($field_config_name), SafeMarkup::format('Deleted field: !field', array('!field' => $field_config_name)));
    $this->assertTrue($staging->delete($field_config_name_2a), SafeMarkup::format('Deleted field: !field', array('!field' => $field_config_name_2a)));
    $this->assertTrue($staging->delete($field_config_name_2b), SafeMarkup::format('Deleted field: !field', array('!field' => $field_config_name_2b)));

    $deletes = $this->configImporter()->getUnprocessedConfiguration('delete');
    $this->assertEqual(count($deletes), 5, 'Importing configuration will delete 3 fields and 2 field storages.');

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the field storages and fields are gone.
    \Drupal::entityManager()->getStorage('field_storage_config')->resetCache(array($field_storage_id));
    $field_storage = FieldStorageConfig::load($field_storage_id);
    $this->assertFalse($field_storage, 'The field storage was deleted.');
    \Drupal::entityManager()->getStorage('field_storage_config')->resetCache(array($field_storage_id_2));
    $field_storage_2 = FieldStorageConfig::load($field_storage_id_2);
    $this->assertFalse($field_storage_2, 'The second field storage was deleted.');
    \Drupal::entityManager()->getStorage('field_config')->resetCache(array($field_id));
    $field = FieldConfig::load($field_id);
    $this->assertFalse($field, 'The field was deleted.');
    \Drupal::entityManager()->getStorage('field_config')->resetCache(array($field_id_2a));
    $field_2a = FieldConfig::load($field_id_2a);
    $this->assertFalse($field_2a, 'The second field on test bundle was deleted.');
    \Drupal::entityManager()->getStorage('field_config')->resetCache(array($field_id_2b));
    $field_2b = FieldConfig::load($field_id_2b);
    $this->assertFalse($field_2b, 'The second field on test bundle 2 was deleted.');

    // Check that all config files are gone.
    $active = $this->container->get('config.storage');
    $this->assertIdentical($active->listAll($field_storage_config_name), array());
    $this->assertIdentical($active->listAll($field_storage_config_name_2), array());
    $this->assertIdentical($active->listAll($field_config_name), array());
    $this->assertIdentical($active->listAll($field_config_name_2a), array());
    $this->assertIdentical($active->listAll($field_config_name_2b), array());

    // Check that the storage definition is preserved in state.
    $deleted_storages = \Drupal::state()->get('field.storage.deleted') ?: array();
    $this->assertTrue(isset($deleted_storages[$field_storage_uuid]));
    $this->assertTrue(isset($deleted_storages[$field_storage_uuid_2]));

    // Purge field data, and check that the storage definition has been
    // completely removed once the data is purged.
    field_purge_batch(10);
    $deleted_storages = \Drupal::state()->get('field.storage.deleted') ?: array();
    $this->assertTrue(empty($deleted_storages), 'Fields are deleted');
  }
}

