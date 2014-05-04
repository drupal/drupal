<?php

/**
 * @file
 * Contains Drupal\config\Tests\DefaultConfigTest.
 */

namespace Drupal\config\Tests;

use Drupal\config_test\TestInstallStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\TypedConfigManager;

/**
 * Tests default configuration availability and type with configuration schema.
 */
class DefaultConfigTest extends ConfigSchemaTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Default configuration',
      'description' => 'Tests that default configuration provided by all modules matches schema.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests default configuration data type.
   */
  public function testDefaultConfig() {
    // Create a typed config manager with access to configuration schema in
    // every module, profile and theme.
    $typed_config = new TypedConfigManager(
      \Drupal::service('config.storage'),
      new TestInstallStorage(InstallStorage::CONFIG_SCHEMA_DIRECTORY),
      \Drupal::service('cache.config')
    );

    // Create a configuration storage with access to default configuration in
    // every module, profile and theme.
    $default_config_storage = new TestInstallStorage();

    foreach ($default_config_storage->listAll() as $config_name) {
      // @todo: remove once migration (https://drupal.org/node/2183957) and
      // translation (https://drupal.org/node/2168609) schemas are in.
      if (strpos($config_name, 'migrate.migration') === 0 || strpos($config_name, 'language.config') === 0) {
        continue;
      }

      // 1. config_test.noschema has to be skipped as it tests
      // TypedConfigManagerInterface::hasConfigSchema() method.
      // 2. config.someschema has to be skipped as it tests schema default data
      // type fallback.
      // 3. config_test.schema_in_install is testing that schema are used during
      // configuration installation.
      if ($config_name == 'config_test.noschema' || $config_name == 'config_test.someschema' || $config_name == 'config_test.schema_in_install') {
        continue;
      }

      $data = $default_config_storage->read($config_name);
      $this->assertConfigSchema($typed_config, $config_name, $data);
    }
  }

}
