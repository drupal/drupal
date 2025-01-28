<?php

declare(strict_types=1);

namespace Drupal\Tests\options\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Functional\FieldTestBase;

/**
 * Tests option fields can be updated and created by config synchronization.
 *
 * @group options
 */
class OptionsFloatFieldImportTest extends FieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'options',
    'field_ui',
    'config',
    'options_config_install_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'synchronize configuration',
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer node fields',
      'administer node display',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that importing list_float fields works.
   */
  public function testImport(): void {
    $field_name = 'field_options_float';
    $type = 'options_install_test';

    // Test the results on installing options_config_install_test. All the
    // necessary configuration for this test is created by installing that
    // module.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertSame($array = ['0' => 'Zero', '0.5' => 'Point five'], $field_storage->getSetting('allowed_values'));

    $admin_path = 'admin/structure/types/manage/' . $type . '/fields/node.' . $type . '.' . $field_name;

    // Export active config to sync.
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set the active to not use dots in the allowed values key names.
    $edit = [
      'field_storage[subform][settings][allowed_values][table][0][item][key]' => 0,
      'field_storage[subform][settings][allowed_values][table][0][item][label]' => 'Zero',
      'field_storage[subform][settings][allowed_values][table][1][item][key]' => 1,
      'field_storage[subform][settings][allowed_values][table][1][item][label]' => 'One',
    ];
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save');
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertSame($array = ['0' => 'Zero', '1' => 'One'], $field_storage->getSetting('allowed_values'));

    // Import configuration with dots in the allowed values key names. This
    // tests \Drupal\Core\Config\Entity\ConfigEntityStorage::importUpdate().
    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertSame($array = ['0' => 'Zero', '0.5' => 'Point five'], $field_storage->getSetting('allowed_values'));

    // Delete field to test creation. This tests
    // \Drupal\Core\Config\Entity\ConfigEntityStorage::importCreate().
    FieldConfig::loadByName('node', $type, $field_name)->delete();

    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertSame($array = ['0' => 'Zero', '0.5' => 'Point five'], $field_storage->getSetting('allowed_values'));
  }

}
