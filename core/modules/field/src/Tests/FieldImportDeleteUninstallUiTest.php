<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportDeleteUninstallUiTest.
 */

namespace Drupal\field\Tests;

/**
 * Delete field storages and fields during config synchronization and uninstall
 * module that provides the field type through the UI.
 *
 * @group field
 * @see \Drupal\field\ConfigImporterFieldPurger
 * @see field_config_import_steps_alter()
 * @see field_form_config_admin_import_form_alter()
 */
class FieldImportDeleteUninstallUiTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'telephone', 'config', 'filter', 'datetime');

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(array('synchronize configuration')));
  }

  /**
   * Tests deleting field storages and fields as part of config import.
   */
  public function testImportDeleteUninstall() {
    // Create a telephone field.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_tel',
      'entity_type' => 'entity_test',
      'type' => 'telephone',
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ))->save();

    // Create a text field.
    $date_field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_date',
      'entity_type' => 'entity_test',
      'type' => 'datetime',
    ));
    $date_field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $date_field_storage,
      'bundle' => 'entity_test',
    ))->save();

    // Create an entity which has values for the telephone and text field.
    $entity = entity_create('entity_test');
    $value = '+0123456789';
    $entity->field_tel = $value;
    $entity->field_date = time();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Delete the text field before exporting configuration so that we can test
    // that deleted fields that are provided by modules that will be uninstalled
    // are also purged and that the UI message includes such fields.
    $date_field_storage->delete();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_tel->value, $value);
    $this->assertEqual($entity->field_tel[0]->value, $value);

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    // Stage uninstall of the Telephone module.
    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['telephone']);
    $staging->write('core.extension', $core_extension);

    // Stage the field deletion
    $staging->delete('field.storage.entity_test.field_tel');
    $staging->delete('field.field.entity_test.entity_test.field_tel');
    $this->drupalGet('admin/config/development/configuration');
    // Test that the message for one field being purged during a configuration
    // synchronization is correct.
    $this->assertText('This synchronization will delete data from the field entity_test.field_tel.');

    // Stage an uninstall of the datetime module to test the message for
    // multiple fields.
    unset($core_extension['module']['datetime']);
    $staging->write('core.extension', $core_extension);

    $this->drupalGet('admin/config/development/configuration');
    $this->assertText('This synchronization will delete data from the fields: entity_test.field_tel, entity_test.field_date.');

    // This will purge all the data, delete the field and uninstall the
    // Telephone and Text modules.
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $this->assertNoText('Field data will be deleted by this synchronization.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('telephone'));
    $this->assertFalse(\Drupal::entityManager()->loadEntityByUuid('field_storage_config', $field_storage->uuid()), 'The telephone field has been deleted by the configuration synchronization');
    $deleted_storages = \Drupal::state()->get('field.storage.deleted') ?: array();
    $this->assertFalse(isset($deleted_storages[$field_storage->uuid()]), 'Telephone field has been completed removed from the system.');
    $this->assertFalse(isset($deleted_storages[$field_storage->uuid()]), 'Text field has been completed removed from the system.');
  }

}
