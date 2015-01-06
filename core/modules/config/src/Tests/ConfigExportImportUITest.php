<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigExportImportUITest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the user interface for importing/exporting configuration.
 *
 * Each testX method does a complete rebuild of a Drupal site, so values being
 * tested need to be stored in protected properties in order to survive until
 * the next rebuild.
 *
 * @group config
 */
class ConfigExportImportUITest extends WebTestBase {

  /**
   * The contents of the config export tarball, held between test methods.
   *
   * @var string
   */
  protected $tarball;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config', 'node', 'field');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // The initial import must be done with uid 1 because if separately named
    // roles are created then the role is lost after import. If the roles
    // created have the same name then the sync will fail because they will
    // have different UUIDs.
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests a simple site export import case.
   */
  public function testExportImport() {
    $this->originalSlogan = $this->config('system.site')->get('slogan');
    $this->newSlogan = $this->randomString(16);
    $this->assertNotEqual($this->newSlogan, $this->originalSlogan);
    $this->config('system.site')
      ->set('slogan', $this->newSlogan)
      ->save();
    $this->assertEqual($this->config('system.site')->get('slogan'), $this->newSlogan);

    // Create a content type.
    $this->content_type = $this->drupalCreateContentType();

    // Create a field.
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text',
    ));
    $this->fieldStorage->save();
    entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->content_type->type,
    ))->save();
    entity_get_form_display('node', $this->content_type->type, 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'text_textfield',
      ))
      ->save();
    entity_get_display('node', $this->content_type->type, 'full')
      ->setComponent($this->fieldName)
      ->save();

    $this->drupalGet('node/add/' . $this->content_type->type);
    $this->assertFieldByName("{$this->fieldName}[0][value]", '', 'Widget is displayed');

    // Export the configuration.
    $this->drupalPostForm('admin/config/development/configuration/full/export', array(), 'Export');
    $this->tarball = $this->drupalGetContent();

    $this->config('system.site')
      ->set('slogan', $this->originalSlogan)
      ->save();
    $this->assertEqual($this->config('system.site')->get('slogan'), $this->originalSlogan);

    // Delete the custom field.
    $fields = FieldConfig::loadMultiple();
    foreach ($fields as $field) {
      if ($field->field_name == $this->fieldName) {
        $field->delete();
      }
    }
    $field_storages = entity_load_multiple('field_storage_config');
    foreach ($field_storages as $field_storage) {
      if ($field_storage->field_name == $this->fieldName) {
        $field_storage->delete();
      }
    }
    $this->drupalGet('node/add/' . $this->content_type->type);
    $this->assertNoFieldByName("{$this->fieldName}[0][value]", '', 'Widget is not displayed');

    // Import the configuration.
    $filename = 'temporary://' . $this->randomMachineName();
    file_put_contents($filename, $this->tarball);
    $this->drupalPostForm('admin/config/development/configuration/full/import', array('files[import_tarball]' => $filename), 'Upload');
    $this->drupalPostForm(NULL, array(), 'Import all');

    $this->assertEqual($this->config('system.site')->get('slogan'), $this->newSlogan);

    $this->drupalGet('node/add');
    $this->assertFieldByName("{$this->fieldName}[0][value]", '', 'Widget is displayed');

    $this->config('system.site')
      ->set('slogan', $this->originalSlogan)
      ->save();
    $this->drupalGet('admin/config/development/configuration');
    $this->assertText('Your current configuration has changed. Changes to these configuration items will be lost on the next synchronization: system.site');
  }

  /**
   * Tests an export and import of collections.
   */
  public function testExportImportCollections() {

    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $test1_storage = $active_storage->createCollection('collection.test1');
    $test1_storage->write('config_test.create', array('foo' => 'bar'));
    $test1_storage->write('config_test.update', array('foo' => 'bar'));
    $test2_storage = $active_storage->createCollection('collection.test2');
    $test2_storage->write('config_test.another_create', array('foo' => 'bar'));
    $test2_storage->write('config_test.another_update', array('foo' => 'bar'));

    // Export the configuration.
    $this->drupalPostForm('admin/config/development/configuration/full/export', array(), 'Export');
    $this->tarball = $this->drupalGetContent();
    $filename = file_directory_temp() .'/' . $this->randomMachineName();
    file_put_contents($filename, $this->tarball);

    // Set up the active storage collections to test import.
    $test1_storage->delete('config_test.create');
    $test1_storage->write('config_test.update', array('foo' => 'baz'));
    $test1_storage->write('config_test.delete', array('foo' => 'bar'));
    $test2_storage->delete('config_test.another_create');
    $test2_storage->write('config_test.another_update', array('foo' => 'baz'));
    $test2_storage->write('config_test.another_delete', array('foo' => 'bar'));

    // Create a snapshot.
    $snapshot_storage = \Drupal::service('config.storage.snapshot');
    \Drupal::service('config.manager')->createSnapshot($active_storage, $snapshot_storage);

    // Ensure that the snapshot has the expected collection data before import.
    $test1_snapshot = $snapshot_storage->createCollection('collection.test1');
    $data = $test1_snapshot->read('config_test.delete');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.delete in collection.test1 exists in the snapshot storage.');
    $data = $test1_snapshot->read('config_test.update');
    $this->assertEqual($data, array('foo' => 'baz'), 'The config_test.update in collection.test1 exists in the snapshot storage.');
    $this->assertFalse($test1_snapshot->read('config_test.create'), 'The config_test.create in collection.test1 does not exist in the snapshot storage.');
    $test2_snapshot = $snapshot_storage->createCollection('collection.test2');
    $data = $test2_snapshot->read('config_test.another_delete');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.another_delete in collection.test2 exists in the snapshot storage.');
    $data = $test2_snapshot->read('config_test.another_update');
    $this->assertEqual($data, array('foo' => 'baz'), 'The config_test.another_update in collection.test2 exists in the snapshot storage.');
    $this->assertFalse($test2_snapshot->read('config_test.another_create'), 'The config_test.another_create in collection.test2 does not exist in the snapshot storage.');

    // Create the tar contains the expected contect for the collections.
    $tar = new ArchiveTar($filename, 'gz');
    $content_list = $tar->listContent();
    // Convert the list of files into something easy to search.
    $files = array();
    foreach ($content_list as $file) {
      $files[] = $file['filename'];
    }
    $this->assertTrue(in_array('collection/test1/config_test.create.yml', $files), 'Config export contains collection/test1/config_test.create.yml.');
    $this->assertTrue(in_array('collection/test2/config_test.another_create.yml', $files), 'Config export contains collection/test2/config_test.another_create.yml.');
    $this->assertTrue(in_array('collection/test1/config_test.update.yml', $files), 'Config export contains collection/test1/config_test.update.yml.');
    $this->assertTrue(in_array('collection/test2/config_test.another_update.yml', $files), 'Config export contains collection/test2/config_test.another_update.yml.');
    $this->assertFalse(in_array('collection/test1/config_test.delete.yml', $files), 'Config export does not contain collection/test1/config_test.delete.yml.');
    $this->assertFalse(in_array('collection/test2/config_test.another_delete.yml', $files), 'Config export does not contain collection/test2/config_test.another_delete.yml.');

    $this->drupalPostForm('admin/config/development/configuration/full/import', array('files[import_tarball]' => $filename), 'Upload');
    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertNoText(t('There are no configuration changes to import.'));
    $this->assertText(t('!collection configuration collection', array('!collection' => 'collection.test1')));
    $this->assertText(t('!collection configuration collection', array('!collection' => 'collection.test2')));
    $this->assertText('config_test.create');
    $this->assertLinkByHref('admin/config/development/configuration/sync/diff_collection/collection.test1/config_test.create');
    $this->assertText('config_test.update');
    $this->assertLinkByHref('admin/config/development/configuration/sync/diff_collection/collection.test1/config_test.update');
    $this->assertText('config_test.delete');
    $this->assertLinkByHref('admin/config/development/configuration/sync/diff_collection/collection.test1/config_test.delete');
    $this->assertText('config_test.another_create');
    $this->assertLinkByHref('admin/config/development/configuration/sync/diff_collection/collection.test2/config_test.another_create');
    $this->assertText('config_test.another_update');
    $this->assertLinkByHref('admin/config/development/configuration/sync/diff_collection/collection.test2/config_test.another_update');
    $this->assertText('config_test.another_delete');
    $this->assertLinkByHref('admin/config/development/configuration/sync/diff_collection/collection.test2/config_test.another_delete');

    $this->drupalPostForm(NULL, array(), 'Import all');
    $this->assertText(t('There are no configuration changes to import.'));

    // Test data in collections.
    $data = $test1_storage->read('config_test.create');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.create in collection.test1 has been created.');
    $data = $test1_storage->read('config_test.update');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.update in collection.test1 has been updated.');
    $this->assertFalse($test1_storage->read('config_test.delete'), 'The config_test.delete in collection.test1 has been deleted.');

    $data = $test2_storage->read('config_test.another_create');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.another_create in collection.test2 has been created.');
    $data = $test2_storage->read('config_test.another_update');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.another_update in collection.test2 has been updated.');
    $this->assertFalse($test2_storage->read('config_test.another_delete'), 'The config_test.another_delete in collection.test2 has been deleted.');

    // Ensure that the snapshot has been updated with the collection data.
    $snapshot_storage = \Drupal::service('config.storage.snapshot');
    $test1_snapshot = $snapshot_storage->createCollection('collection.test1');
    $data = $test1_snapshot->read('config_test.create');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.create in collection.test1 has been created in the snapshot storage.');
    $data = $test1_snapshot->read('config_test.update');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.update in collection.test1 has been updated in the snapshot storage.');
    $this->assertFalse($test1_snapshot->read('config_test.delete'), 'The config_test.delete in collection.test1 does not exist in the snapshot storage.');
    $test2_snapshot = $snapshot_storage->createCollection('collection.test2');
    $data = $test2_snapshot->read('config_test.another_create');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.another_create in collection.test2 has been created in the snapshot storage.');
    $data = $test2_snapshot->read('config_test.another_update');
    $this->assertEqual($data, array('foo' => 'bar'), 'The config_test.another_update in collection.test2 has been updated in the snapshot storage.');
    $this->assertFalse($test2_snapshot->read('config_test.another_delete'), 'The config_test.another_delete in collection.test2 does not exist in the snapshot storage.');
  }

}
