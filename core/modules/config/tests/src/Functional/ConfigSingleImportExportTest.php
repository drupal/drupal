<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface for importing/exporting a single configuration.
 *
 * @group config
 */
class ConfigSingleImportExportTest extends BrowserTestBase {

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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests importing a single configuration file.
   */
  public function testImport() {
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');
    $uuid = \Drupal::service('uuid');

    $this->drupalLogin($this->drupalCreateUser(['import configuration']));

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
    $edit = [
      'config_type' => 'config_test',
      'import' => $import,
    ];
    // Attempt an import with a missing ID.
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertText(t('Missing ID key "@id_key" for this @entity_type import.', ['@id_key' => 'id', '@entity_type' => 'Test configuration']));

    // Perform an import with no specified UUID and a unique ID.
    $this->assertNull($storage->load('first'));
    $edit['import'] = "id: first\n" . $edit['import'];
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to create a new %name @type?', ['%name' => 'first', '@type' => 'test configuration']));
    $this->drupalPostForm(NULL, [], t('Confirm'));
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
    $this->assertRaw(t('Are you sure you want to create a new %name @type?', ['%name' => 'custom_id', '@type' => 'test configuration']));
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertRaw(t('The configuration was imported successfully.'));

    // Perform an import with a unique ID and UUID.
    $import = <<<EOD
id: second
label: Second
weight: 0
style: ''
status: '0'
EOD;
    $edit = [
      'config_type' => 'config_test',
      'import' => $import,
    ];
    $second_uuid = $uuid->generate();
    $edit['import'] .= "\nuuid: " . $second_uuid;
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to create a new %name @type?', ['%name' => 'second', '@type' => 'test configuration']));
    $this->drupalPostForm(NULL, [], t('Confirm'));
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
    $edit = [
      'config_type' => 'config_test',
      'import' => $import,
    ];
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to update the %name @type?', ['%name' => 'second', '@type' => 'test configuration']));
    $this->drupalPostForm(NULL, [], t('Confirm'));
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
    $edit = [
      'config_type' => 'config_test',
      'import' => $import,
    ];
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Configuration %name depends on the %owner module that will not be installed after import.', ['%name' => 'config_test.dynamic.second', '%owner' => 'does_not_exist']));

    // Try to preform an update which would create a PHP object if Yaml parsing
    // not securely set up.
    // Perform an update.
    $import = <<<EOD
id: second
uuid: $second_uuid
label: !php/object "O:36:\"Drupal\\\Core\\\Test\\\ObjectSerialization\":0:{}"
weight: 0
style: ''
status: '0'
EOD;
    $edit = [
      'config_type' => 'config_test',
      'import' => $import,
    ];
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    if (extension_loaded('yaml')) {
      // If the yaml extension is loaded it will work but not create the PHP
      // object.
      $this->assertRaw(t('Are you sure you want to update the %name @type?', [
        '%name' => 'second',
        '@type' => 'test configuration',
      ]));
      $this->drupalPostForm(NULL, [], t('Confirm'));
      $entity = $storage->load('second');
      $this->assertRaw(t('The configuration was imported successfully.'));
      $this->assertIsString($entity->label());
      $this->assertStringContainsString('ObjectSerialization', $entity->label(), 'Label contains serialized object');
    }
    else {
      // If the Symfony parser is used there will be an error.
      $this->assertSession()->responseContains('The import failed with the following message:');
      $this->assertSession()->responseContains('Object support when parsing a YAML file has been disabled');
    }
  }

  /**
   * Tests importing a simple configuration file.
   */
  public function testImportSimpleConfiguration() {
    $this->drupalLogin($this->drupalCreateUser(['import configuration']));
    $config = $this->config('system.site')->set('name', 'Test simple import');

    // Place branding block with site name into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    $edit = [
      'config_type' => 'system.simple',
      'config_name' => $config->getName(),
      'import' => Yaml::encode($config->get()),
    ];
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertRaw(t('Are you sure you want to update the %name @type?', ['%name' => $config->getName(), '@type' => 'simple configuration']));
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->drupalGet('');
    $this->assertText('Test simple import');

    // Ensure that ConfigImporter validation is running when importing simple
    // configuration.
    $config_data = $this->config('core.extension')->get();
    // Simulate uninstalling the Config module.
    unset($config_data['module']['config']);
    $edit = [
      'config_type' => 'system.simple',
      'config_name' => 'core.extension',
      'import' => Yaml::encode($config_data),
    ];
    $this->drupalPostForm('admin/config/development/configuration/single/import', $edit, t('Import'));
    $this->assertText(t('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.'));

  }

  /**
   * Tests exporting a single configuration file.
   */
  public function testExport() {
    $this->drupalLogin($this->drupalCreateUser(['export configuration']));

    $this->drupalGet('admin/config/development/configuration/single/export/system.simple');
    $this->assertFieldByXPath('//select[@name="config_type"]//option[@selected="selected"]', t('Simple configuration'), 'The simple configuration option is selected when specified in the URL.');
    // Spot check several known simple configuration files.
    $element = $this->xpath('//select[@name="config_name"]')[0];
    $options = $element->findAll('css', 'option');
    $expected_options = ['system.site', 'user.settings'];
    foreach ($options as &$option) {
      $option = $option->getValue();
    }
    $this->assertIdentical($expected_options, array_intersect($expected_options, $options), 'The expected configuration files are listed.');

    $this->drupalGet('admin/config/development/configuration/single/export/system.simple/system.image');
    $this->assertEquals("toolkit: gd\n_core:\n  default_config_hash: durWHaKeBaq4d9Wpi4RqwADj1OufDepcnJuhVLmKN24\n", $this->xpath('//textarea[@name="export"]')[0]->getValue(), 'The expected system configuration is displayed.');

    $this->drupalGet('admin/config/development/configuration/single/export/date_format');
    $this->assertFieldByXPath('//select[@name="config_type"]//option[@selected="selected"]', t('Date format'), 'The date format entity type is selected when specified in the URL.');

    $this->drupalGet('admin/config/development/configuration/single/export/date_format/fallback');
    $this->assertFieldByXPath('//select[@name="config_name"]//option[@selected="selected"]', t('Fallback date format (fallback)'), 'The fallback date format config entity is selected when specified in the URL.');

    $fallback_date = \Drupal::entityTypeManager()->getStorage('date_format')->load('fallback');
    $yaml_text = $this->xpath('//textarea[@name="export"]')[0]->getValue();
    $this->assertEquals(Yaml::decode($yaml_text), $fallback_date->toArray(), 'The fallback date format config entity export code is displayed.');
  }

}
