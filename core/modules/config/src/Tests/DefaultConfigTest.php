<?php

/**
 * @file
 * Contains Drupal\config\Tests\DefaultConfigTest.
 */

namespace Drupal\config\Tests;

use Drupal\config_test\TestInstallStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests default configuration availability and type with configuration schema.
 */
class DefaultConfigTest extends KernelTestBase {

  use SchemaCheckTestTrait;

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
      \Drupal::service('cache.discovery')
    );

    // Create a configuration storage with access to default configuration in
    // every module, profile and theme.
    $default_config_storage = new TestInstallStorage();

    foreach ($default_config_storage->listAll() as $config_name) {
      // Skip files provided by the config_schema_test module since that module
      // is explicitly for testing schema.
      if (strpos($config_name, 'config_schema_test') === 0) {
        continue;
      }

      $data = $default_config_storage->read($config_name);
      $this->assertConfigSchema($typed_config, $config_name, $data);
    }
  }

}
