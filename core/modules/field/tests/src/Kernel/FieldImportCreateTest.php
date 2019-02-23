<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\field\Entity\FieldConfig;

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Create field storages and fields during config create method invocation.
 *
 * @group field
 */
class FieldImportCreateTest extends FieldKernelTestBase {

  /**
   * Tests creating field storages and fields during default config import.
   */
  public function testImportCreateDefault() {
    $field_name = 'field_test_import';
    $field_storage_id = "entity_test.$field_name";
    $field_id = "entity_test.entity_test.$field_name";
    $field_name_2 = 'field_test_import_2';
    $field_storage_id_2 = "entity_test.$field_name_2";
    $field_id_2a = "entity_test.entity_test.$field_name_2";
    $field_id_2b = "entity_test.test_bundle.$field_name_2";

    // Check that the field storages and fields do not exist yet.
    $this->assertFalse(FieldStorageConfig::load($field_storage_id));
    $this->assertFalse(FieldConfig::load($field_id));
    $this->assertFalse(FieldStorageConfig::load($field_storage_id_2));
    $this->assertFalse(FieldConfig::load($field_id_2a));
    $this->assertFalse(FieldConfig::load($field_id_2b));

    // Create a second bundle for the 'Entity test' entity type.
    entity_test_create_bundle('test_bundle');

    // Enable field_test_config module and check that the field and storage
    // shipped in the module's default config were created.
    \Drupal::service('module_installer')->install(['field_test_config']);

    // A field storage with one single field.
    $field_storage = FieldStorageConfig::load($field_storage_id);
    $this->assertTrue($field_storage, 'The field was created.');
    $field = FieldConfig::load($field_id);
    $this->assertTrue($field, 'The field was deleted.');

    // A field storage with two fields.
    $field_storage_2 = FieldStorageConfig::load($field_storage_id_2);
    $this->assertTrue($field_storage_2, 'The second field was created.');
    $this->assertTrue($field->getTargetBundle(), 'test_bundle', 'The second field was created on bundle test_bundle.');
    $this->assertTrue($field->getTargetBundle(), 'test_bundle_2', 'The second field was created on bundle test_bundle_2.');

    // Tests fields.
    $ids = \Drupal::entityQuery('field_config')
      ->condition('entity_type', 'entity_test')
      ->condition('bundle', 'entity_test')
      ->execute();
    $this->assertEqual(count($ids), 2);
    $this->assertTrue(isset($ids['entity_test.entity_test.field_test_import']));
    $this->assertTrue(isset($ids['entity_test.entity_test.field_test_import_2']));
    $ids = \Drupal::entityQuery('field_config')
      ->condition('entity_type', 'entity_test')
      ->condition('bundle', 'test_bundle')
      ->execute();
    $this->assertEqual(count($ids), 1);
    $this->assertTrue(isset($ids['entity_test.test_bundle.field_test_import_2']));
  }

  /**
   * Tests creating field storages and fields during config import.
   */
  public function testImportCreate() {
    // A field storage with one single field.
    $field_name = 'field_test_import_sync';
    $field_storage_id = "entity_test.$field_name";
    $field_id = "entity_test.entity_test.$field_name";
    $field_storage_config_name = "field.storage.$field_storage_id";
    $field_config_name = "field.field.$field_id";

    // A field storage with two fields.
    $field_name_2 = 'field_test_import_sync_2';
    $field_storage_id_2 = "entity_test.$field_name_2";
    $field_id_2a = "entity_test.test_bundle.$field_name_2";
    $field_id_2b = "entity_test.test_bundle_2.$field_name_2";
    $field_storage_config_name_2 = "field.storage.$field_storage_id_2";
    $field_config_name_2a = "field.field.$field_id_2a";
    $field_config_name_2b = "field.field.$field_id_2b";

    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    // Add the new files to the sync directory.
    $src_dir = __DIR__ . '/../../modules/field_test_config/sync';
    $target_dir = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->assertTrue($file_system->copy("$src_dir/$field_storage_config_name.yml", "$target_dir/$field_storage_config_name.yml"));
    $this->assertTrue($file_system->copy("$src_dir/$field_config_name.yml", "$target_dir/$field_config_name.yml"));
    $this->assertTrue($file_system->copy("$src_dir/$field_storage_config_name_2.yml", "$target_dir/$field_storage_config_name_2.yml"));
    $this->assertTrue($file_system->copy("$src_dir/$field_config_name_2a.yml", "$target_dir/$field_config_name_2a.yml"));
    $this->assertTrue($file_system->copy("$src_dir/$field_config_name_2b.yml", "$target_dir/$field_config_name_2b.yml"));

    // Import the content of the sync directory.
    $this->configImporter()->import();

    // Check that the field and storage were created.
    $field_storage = FieldStorageConfig::load($field_storage_id);
    $this->assertTrue($field_storage, 'Test import storage field from sync exists');
    $field = FieldConfig::load($field_id);
    $this->assertTrue($field, 'Test import field  from sync exists');
    $field_storage = FieldStorageConfig::load($field_storage_id_2);
    $this->assertTrue($field_storage, 'Test import storage field 2 from sync exists');
    $field = FieldConfig::load($field_id_2a);
    $this->assertTrue($field, 'Test import field 2a from sync exists');
    $field = FieldConfig::load($field_id_2b);
    $this->assertTrue($field, 'Test import field 2b from sync exists');
  }

}
