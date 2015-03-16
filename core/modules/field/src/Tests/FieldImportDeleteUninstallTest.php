<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportDeleteUninstallTest.
 */

namespace Drupal\field\Tests;

/**
 * Delete field storages and fields during config synchronization and uninstall
 * module that provides the field type.
 *
 * @group field
 * @see \Drupal\field\ConfigImporterFieldPurger
 * @see field_config_import_steps_alter()
 */
class FieldImportDeleteUninstallTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('telephone');

  protected function setUp() {
    parent::setUp();
    // Module uninstall requires the router and users_data tables.
    // @see drupal_flush_all_caches()
    // @see user_modules_uninstalled()
    $this->installSchema('user', array('users_data'));
  }

  /**
   * Tests deleting field storages and fields as part of config import.
   */
  public function testImportDeleteUninstall() {
    // Create a field to delete to prove that
    // \Drupal\field\ConfigImporterFieldPurger does not purge fields that are
    // not related to the configuration synchronization.
    $unrelated_field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_int',
      'entity_type' => 'entity_test',
      'type' => 'integer',
    ));
    $unrelated_field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $unrelated_field_storage,
      'bundle' => 'entity_test',
    ))->save();

    // Create a telephone field for validation.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'telephone',
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ))->save();

    $entity = entity_create('entity_test');
    $value = '+0123456789';
    $entity->field_test = $value;
    $entity->field_int = '99';
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_test->value, $value);
    $this->assertEqual($entity->field_test[0]->value, $value);
    $this->assertEqual($entity->field_int->value, '99');

    // Delete unrelated field before copying configuration and running the
    // synchronization.
    $unrelated_field_storage->delete();

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    // Stage uninstall of the Telephone module.
    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['telephone']);
    $staging->write('core.extension', $core_extension);

    // Stage the field deletion
    $staging->delete('field.storage.entity_test.field_test');
    $staging->delete('field.field.entity_test.entity_test.field_test');

    $steps = $this->configImporter()->initialize();
    $this->assertIdentical($steps[0], array('\Drupal\field\ConfigImporterFieldPurger', 'process'), 'The additional process configuration synchronization step has been added.');

    // This will purge all the data, delete the field and uninstall the
    // Telephone module.
    $this->configImporter()->import();

    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('telephone'));
    $this->assertFalse(entity_load_by_uuid('field_storage_config', $field_storage->uuid()), 'The test field has been deleted by the configuration synchronization');
    $deleted_storages = \Drupal::state()->get('field.storage.deleted') ?: array();
    $this->assertFalse(isset($deleted_storages[$field_storage->uuid()]), 'Telephone field has been completed removed from the system.');
    $this->assertTrue(isset($deleted_storages[$unrelated_field_storage->uuid()]), 'Unrelated field not purged by configuration synchronization.');
  }

  /**
   * Tests purging already deleted field storages and fields during a config
   * import.
   */
  public function testImportAlreadyDeletedUninstall() {
    // Create a telephone field for validation.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'telephone',
    ));
    $field_storage->save();
    $field_storage_uuid = $field_storage->uuid();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ))->save();

    // Create 12 entities to ensure that the purging works as expected.
    for ($i=0; $i < 12; $i++) {
      $entity = entity_create('entity_test');
      $value = '+0123456789';
      $entity->field_test = $value;
      $entity->name->value = $this->randomMachineName();
      $entity->save();

      // Verify entity has been created properly.
      $id = $entity->id();
      $entity = entity_load('entity_test', $id);
      $this->assertEqual($entity->field_test->value, $value);
    }

    // Delete the field.
    $field_storage->delete();

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    // Stage uninstall of the Telephone module.
    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['telephone']);
    $staging->write('core.extension', $core_extension);

    $deleted_storages = \Drupal::state()->get('field.storage.deleted') ?: array();
    $this->assertTrue(isset($deleted_storages[$field_storage_uuid]), 'Field has been deleted and needs purging before configuration synchronization.');

    $steps = $this->configImporter()->initialize();
    $this->assertIdentical($steps[0], array('\Drupal\field\ConfigImporterFieldPurger', 'process'), 'The additional process configuration synchronization step has been added.');

    // This will purge all the data, delete the field and uninstall the
    // Telephone module.
    $this->configImporter()->import();

    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('telephone'));
    $deleted_storages = \Drupal::state()->get('field.storage.deleted') ?: array();
    $this->assertFalse(isset($deleted_storages[$field_storage_uuid]), 'Field has been completed removed from the system.');
  }

}
