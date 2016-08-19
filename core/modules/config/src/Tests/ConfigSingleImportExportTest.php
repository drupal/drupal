<?php

namespace Drupal\config\Tests;

use Drupal\Core\Serialization\Yaml;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the user interface for importing/exporting a single configuration.
 *
 * @group config
 */
class ConfigSingleImportExportTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'config',
    'config_test',
    // Adding language module makes it possible to involve non-default
    // (language.xx) collections in import/export operations.
    'language',
  ];

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests importing a single configuration file.
   */
  public function testImport() {
    $storage = \Drupal::entityManager()->getStorage('config_test');
    $uuid = \Drupal::service('uuid');

    $this->drupalLogin($this->drupalCreateUser(array('import configuration')));

    // Attempt an import with invalid YAML.
    $edit = [
      'config_type' => 'action',
      'import' => '{{{',
    ];

    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    // Assert the static portion of the error since different parsers could give different text in their error.
    $this->assertText('The import failed with the following message: ');

    $import = <<<EOD
label: First
weight: 0
style: ''
status: '1'
EOD;
    $edit = array(
      'config_type' => 'config_test',
      'import' => $import,
    );
    // Attempt an import with a missing ID.
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertText(t('Missing ID key "@id_key" for this @entity_type import.', array('@id_key' => 'id', '@entity_type' => 'Test configuration')));

    // Perform an import with no specified UUID and a unique ID.
    $this->assertNull($storage->load('first'));
    $edit['import'] = "id: first\n" . $edit['import'];
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to create a new %name @type?', array('%name' => 'first', '@type' => 'test configuration')));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $entity = $storage->load('first');
    $this->assertIdentical($entity->label(), 'First');
    $this->assertIdentical($entity->id(), 'first');
    $this->assertTrue($entity->status());
    $this->assertRaw(t('The configuration was imported successfully.'));

    // Attempt an import with an existing ID but missing UUID.
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertText(t('An entity with this machine name already exists but the import did not specify a UUID.'));

    // Attempt an import with a mismatched UUID and existing ID.
    $edit['import'] .= "\nuuid: " . $uuid->generate();
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertText(t('An entity with this machine name already exists but the UUID does not match.'));

    // Attempt an import with a custom ID.
    $edit['custom_entity_id'] = 'custom_id';
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to create a new %name @type?', array('%name' => 'custom_id', '@type' => 'test configuration')));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $this->assertRaw(t('The configuration was imported successfully.'));

    // Perform an import with a unique ID and UUID.
    $import = <<<EOD
id: second
label: Second
weight: 0
style: ''
status: '0'
EOD;
    $edit = array(
      'config_type' => 'config_test',
      'import' => $import,
    );
    $second_uuid = $uuid->generate();
    $edit['import'] .= "\nuuid: " . $second_uuid;
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to create a new %name @type?', array('%name' => 'second', '@type' => 'test configuration')));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $entity = $storage->load('second');
    $this->assertRaw(t('The configuration was imported successfully.'));
    $this->assertIdentical($entity->label(), 'Second');
    $this->assertIdentical($entity->id(), 'second');
    $this->assertFalse($entity->status());
    $this->assertIdentical($entity->uuid(), $second_uuid);

    // Perform an update.
    $import = <<<EOD
id: second
uuid: $second_uuid
label: 'Second updated'
weight: 0
style: ''
status: '0'
EOD;
    $edit = array(
      'config_type' => 'config_test',
      'import' => $import,
    );
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to update the %name @type?', array('%name' => 'second', '@type' => 'test configuration')));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $entity = $storage->load('second');
    $this->assertRaw(t('The configuration was imported successfully.'));
    $this->assertIdentical($entity->label(), 'Second updated');

    // Try to perform an update which adds missing dependencies.
    $import = <<<EOD
id: second
uuid: $second_uuid
label: 'Second updated'
weight: 0
style: ''
status: '0'
dependencies:
  module:
    - does_not_exist
EOD;
    $edit = array(
      'config_type' => 'config_test',
      'import' => $import,
    );
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Configuration %name depends on the %owner module that will not be installed after import.', ['%name' => 'config_test.dynamic.second', '%owner' => 'does_not_exist']));
  }

  /**
   * Tests importing a simple configuration file.
   */
  public function testImportSimpleConfiguration() {
    $this->drupalLogin($this->drupalCreateUser(array('import configuration')));
    $config = $this->config('system.site')->set('name', 'Test simple import');

    // Place branding block with site name into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    $edit = array(
      'config_type' => 'system.simple',
      'config_name' => $config->getName(),
      'import' => Yaml::encode($config->get()),
    );
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to update the %name @type?', array('%name' => $config->getName(), '@type' => 'simple configuration')));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $this->drupalGet('');
    $this->assertText('Test simple import');

    // Ensure that ConfigImporter validation is running when importing simple
    // configuration.
    $config_data = $this->config('core.extension')->get();
    // Simulate uninstalling the Config module.
    unset($config_data['module']['config']);
    $edit = array(
      'config_type' => 'system.simple',
      'config_name' => 'core.extension',
      'import' => Yaml::encode($config_data),
    );
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertText(t('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.'));

  }

  /**
   * Tests exporting a single configuration file.
   */
  public function testExport() {
    $this->drupalLogin($this->drupalCreateUser(array('export configuration')));

    $this->drupalGet('admin/config/development/configuration/single/export/system.simple');
    $this->assertFieldByXPath('//select[@name="config_type"]//option[@selected="selected"]', t('Simple configuration'), 'The simple configuration option is selected when specified in the URL.');
    // Spot check several known simple configuration files.
    $element = $this->xpath('//select[@name="config_name"]');
    $options = $this->getAllOptions($element[0]);
    $expected_options = array('system.site', 'user.settings');
    foreach ($options as &$option) {
      $option = (string) $option;
    }
    $this->assertIdentical($expected_options, array_intersect($expected_options, $options), 'The expected configuration files are listed.');

    $this->drupalGet('admin/config/development/configuration/single/export/system.simple/system.image');
    $this->assertFieldByXPath('//textarea[@name="export"]', "toolkit: gd\n_core:\n  default_config_hash: durWHaKeBaq4d9Wpi4RqwADj1OufDepcnJuhVLmKN24\n", 'The expected system configuration is displayed.');

    $this->drupalGet('admin/config/development/configuration/single/export/date_format');
    $this->assertFieldByXPath('//select[@name="config_type"]//option[@selected="selected"]', t('Date format'), 'The date format entity type is selected when specified in the URL.');

    $this->drupalGet('admin/config/development/configuration/single/export/date_format/fallback');
    $this->assertFieldByXPath('//select[@name="config_name"]//option[@selected="selected"]', t('Fallback date format (fallback)'), 'The fallback date format config entity is selected when specified in the URL.');

    $fallback_date = \Drupal::entityManager()->getStorage('date_format')->load('fallback');
    $yaml_text = (string) $this->xpath('//textarea[@name="export"]')[0];
    $this->assertEqual(Yaml::decode($yaml_text), $fallback_date->toArray(), 'The fallback date format config entity export code is displayed.');
  }

}
