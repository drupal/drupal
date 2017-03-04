<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\config_test\TestInstallStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests that default configuration provided by all modules matches schema.
 *
 * @group config
 */
class DefaultConfigTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'config_test'];

  /**
   * Themes which provide default configuration and need enabling.
   *
   * If a theme provides default configuration but does not have a schema
   * because it can rely on schemas added by system_config_schema_info_alter()
   * then this test needs to enable it.
   *
   * @var array
   */
  protected $themes = ['seven'];

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install($this->themes);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('default_config_test.schema_storage')
      ->setClass('\Drupal\config_test\TestInstallStorage')
      ->addArgument(InstallStorage::CONFIG_SCHEMA_DIRECTORY);

    $definition = $container->getDefinition('config.typed');
    $definition->replaceArgument(1, new Reference('default_config_test.schema_storage'));
  }

  /**
   * Tests default configuration data type.
   */
  public function testDefaultConfig() {
    $typed_config = \Drupal::service('config.typed');
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
