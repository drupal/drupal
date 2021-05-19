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
  protected static $modules = ['system', 'config_test'];

  /**
   * Config files to be ignored by this test.
   *
   * @var array
   */
  protected $toSkip = [
    // Skip files provided by the config_schema_test module since that module
    // is explicitly for testing schema.
    'config_schema_test.ignore',
    'config_schema_test.noschema',
    'config_schema_test.plugin_types',
    'config_schema_test.someschema.somemodule.section_one.subsection',
    'config_schema_test.someschema.somemodule.section_two.subsection',
    'config_schema_test.someschema.with_parents',
    'config_schema_test.someschema',
    // Skip tour-test-legacy files as they intentionally have deprecated
    // properties.
    'tour.tour.tour-test-legacy',
    'tour.tour.tour-test-legacy-location',
  ];

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

  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install($this->themes);
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
      if (in_array($config_name, $this->toSkip)) {
        continue;
      }

      $data = $default_config_storage->read($config_name);
      $this->assertConfigSchema($typed_config, $config_name, $data);
    }
  }

}
