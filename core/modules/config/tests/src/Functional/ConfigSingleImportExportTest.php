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
  protected static $modules = [
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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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

    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    // Assert the static portion of the error since different parsers could give different text in their error.
    $this->assertSession()->pageTextContains('The import failed with the following message: ');

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
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Missing ID key "id" for this Test configuration import.');

    // Perform an import with no specified UUID and a unique ID.
    $this->assertNull($storage->load('first'));
    $edit['import'] = "id: first\n" . $edit['import'];
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Are you sure you want to create a new first test configuration?');
    $this->submitForm([], 'Confirm');
    $entity = $storage->load('first');
    $this->assertSame('First', $entity->label());
    $this->assertSame('first', $entity->id());
    $this->assertTrue($entity->status());
    $this->assertSession()->pageTextContains('The configuration was imported successfully.');

    // Attempt an import with an existing ID but missing UUID.
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('An entity with this machine name already exists but the import did not specify a UUID.');

    // Attempt an import with a mismatched UUID and existing ID.
    $edit['import'] .= "\nuuid: " . $uuid->generate();
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('An entity with this machine name already exists but the UUID does not match.');

    // Attempt an import with a custom ID.
    $edit['custom_entity_id'] = 'custom_id';
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Are you sure you want to create a new custom_id test configuration?');
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('The configuration was imported successfully.');

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
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Are you sure you want to create a new second test configuration?');
    $this->submitForm([], 'Confirm');
    $entity = $storage->load('second');
    $this->assertSession()->pageTextContains('The configuration was imported successfully.');
    $this->assertSame('Second', $entity->label());
    $this->assertSame('second', $entity->id());
    $this->assertFalse($entity->status());
    $this->assertSame($second_uuid, $entity->uuid());

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
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Are you sure you want to update the second test configuration?');
    $this->submitForm([], 'Confirm');
    $entity = $storage->load('second');
    $this->assertSession()->pageTextContains('The configuration was imported successfully.');
    $this->assertSame('Second updated', $entity->label());

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
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Configuration config_test.dynamic.second depends on the does_not_exist module that will not be installed after import.');

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
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    if (extension_loaded('yaml')) {
      // If the yaml extension is loaded it will work but not create the PHP
      // object.
      $this->assertSession()->pageTextContains('Are you sure you want to update the second test configuration?');
      $this->submitForm([], 'Confirm');
      $entity = $storage->load('second');
      $this->assertSession()->pageTextContains('The configuration was imported successfully.');
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
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Are you sure you want to update the ' . $config->getName() . ' simple configuration?');
    $this->submitForm([], 'Confirm');
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Test simple import');

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
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.');

    // Try to import without any values.
    $this->drupalGet('admin/config/development/configuration/single/import');
    $this->submitForm([], 'Import');
    $this->assertSession()->pageTextContains('Configuration type field is required.');
    $this->assertSession()->pageTextContains('Paste your configuration here field is required.');
  }

  /**
   * Tests exporting a single configuration file.
   */
  public function testExport() {
    $this->drupalLogin($this->drupalCreateUser(['export configuration']));

    // Verify that the simple configuration option is selected when specified
    // in the URL.
    $this->drupalGet('admin/config/development/configuration/single/export/system.simple');
    $option_node = $this->assertSession()->optionExists("config_type", 'Simple configuration');
    $this->assertTrue($option_node->isSelected());
    // Spot check several known simple configuration files.
    $this->assertSession()->optionExists('config_name', 'system.site');
    $this->assertSession()->optionExists('config_name', 'user.settings');

    $this->drupalGet('admin/config/development/configuration/single/export/system.simple/system.image');
    // cspell:disable-next-line
    $this->assertSession()->fieldValueEquals('export', "_core:\n  default_config_hash: durWHaKeBaq4d9Wpi4RqwADj1OufDepcnJuhVLmKN24\ntoolkit: gd\n");

    // Verify that the date format entity type is selected when specified in
    // the URL.
    $this->drupalGet('admin/config/development/configuration/single/export/date_format');
    $option_node = $this->assertSession()->optionExists("config_type", 'Date format');
    $this->assertTrue($option_node->isSelected());

    // Verify that the fallback date format config entity is selected when
    // specified in the URL.
    $this->drupalGet('admin/config/development/configuration/single/export/date_format/fallback');
    $option_node = $this->assertSession()->optionExists("config_name", 'Fallback date format (fallback)');
    $this->assertTrue($option_node->isSelected());
    $fallback_date = \Drupal::entityTypeManager()->getStorage('date_format')->load('fallback');
    $yaml_text = $this->assertSession()->fieldExists('export')->getValue();
    $this->assertEquals(Yaml::decode($yaml_text), $fallback_date->toArray(), 'The fallback date format config entity export code is displayed.');
  }

}
