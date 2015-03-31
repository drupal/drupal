<?php

/**
 * @file
 * Contains \Drupal\options\Tests\OptionsFloatFieldImportTest.
 */

namespace Drupal\options\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Tests\FieldTestBase;

/**
 * Tests option fields can be updated and created through config synchronization.
 *
 * @group options
 */
class OptionsFloatFieldImportTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'options', 'field_ui', 'config', 'options_config_install_test');

  protected function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('synchronize configuration', 'access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'bypass node access', 'administer node fields', 'administer node display'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that importing list_float fields works.
   */
  public function testImport() {
    $field_name = 'field_options_float';
    $type = 'options_install_test';

    // Test the results on installing options_config_install_test. All the
    // necessary configuration for this test is created by installing that
    // module.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertIdentical($field_storage->getSetting('allowed_values'), $array = array('0' => 'Zero', '0.5' => 'Point five'));

    $admin_path = 'admin/structure/types/manage/' . $type . '/fields/node.' . $type . '.' . $field_name . '/storage';

    // Export active config to staging
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.staging'));

    // Set the active to not use dots in the allowed values key names.
    $edit = array('settings[allowed_values]' => "0|Zero\n1|One");
    $this->drupalPostForm($admin_path, $edit, t('Save field settings'));
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertIdentical($field_storage->getSetting('allowed_values'), $array = array('0' => 'Zero', '1' => 'One'));

    // Import configuration with dots in the allowed values key names. This
    // tests \Drupal\Core\Config\Entity\ConfigEntityStorage::importUpdate().
    $this->drupalGet('admin/config/development/configuration');
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertIdentical($field_storage->getSetting('allowed_values'), $array = array('0' => 'Zero', '0.5' => 'Point five'));

    // Delete field to test creation. This tests
    // \Drupal\Core\Config\Entity\ConfigEntityStorage::importCreate().
    FieldConfig::loadByName('node', $type, $field_name)->delete();

    $this->drupalGet('admin/config/development/configuration');
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertIdentical($field_storage->getSetting('allowed_values'), $array = array('0' => 'Zero', '0.5' => 'Point five'));
  }

}
