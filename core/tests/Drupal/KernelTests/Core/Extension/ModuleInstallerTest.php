<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\Exception\ObsoleteExtensionException;
use Drupal\Core\Extension\ModuleInstaller;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Tests the ModuleInstaller class.
 *
 * @coversDefaultClass \Drupal\Core\Extension\ModuleInstaller
 *
 * @group Extension
 */
class ModuleInstallerTest extends KernelTestBase implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * Tests that routes are rebuilt during install and uninstall of modules.
   *
   * @covers ::install
   * @covers ::uninstall
   */
  public function testRouteRebuild(): void {
    // Remove the routing table manually to ensure it can be created lazily
    // properly.
    Database::getConnection()->schema()->dropTable('router');

    $this->container->get('module_installer')->install(['router_test']);
    $route = $this->container->get('router.route_provider')->getRouteByName('router_test.1');
    $this->assertEquals('/router_test/test1', $route->getPath());

    $this->container->get('module_installer')->uninstall(['router_test']);
    $this->expectException(RouteNotFoundException::class);
    $this->container->get('router.route_provider')->getRouteByName('router_test.1');
  }

  /**
   * Tests config changes by hook_install() are saved for dependent modules.
   *
   * @covers ::install
   */
  public function testConfigChangeOnInstall(): void {
    // Install the child module so the parent is installed automatically.
    $this->container->get('module_installer')->install(['module_handler_test_multiple_child']);
    $modules = $this->config('core.extension')->get('module');

    $this->assertArrayHasKey('module_handler_test_multiple', $modules, 'Module module_handler_test_multiple is installed');
    $this->assertArrayHasKey('module_handler_test_multiple_child', $modules, 'Module module_handler_test_multiple_child is installed');
    $this->assertEquals(1, $modules['module_handler_test_multiple'], 'Weight of module_handler_test_multiple is set.');
    $this->assertEquals(1, $modules['module_handler_test_multiple_child'], 'Weight of module_handler_test_multiple_child is set.');
  }

  /**
   * Tests cache bins defined by modules are removed when uninstalled.
   *
   * @covers ::removeCacheBins
   */
  public function testCacheBinCleanup(): void {
    $schema = $this->container->get('database')->schema();
    $table = 'cache_module_cache_bin';

    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['module_cache_bin']);

    // Prime the bin.
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache_bin */
    $cache_bin = $this->container->get('module_cache_bin.cache_bin');
    $cache_bin->set('foo', 'bar');

    // A database backend is used so there is a convenient way check whether the
    // backend is uninstalled.
    $this->assertTrue($schema->tableExists($table));

    $module_installer->uninstall(['module_cache_bin']);
    $this->assertFalse($schema->tableExists($table));
  }

  /**
   * Ensure that rebuilding the container in hook_install() works.
   */
  public function testKernelRebuildDuringHookInstall(): void {
    \Drupal::state()->set('module_test_install:rebuild_container', TRUE);
    $module_installer = $this->container->get('module_installer');
    $this->assertTrue($module_installer->install(['module_test']));
  }

  /**
   * Ensure that hooks reacting to install or uninstall are invoked.
   */
  public function testInvokingRespondentHooks(): void {
    $module_installer = $this->container->get('module_installer');
    $this->assertTrue($module_installer->install(['respond_install_uninstall_hook_test']));
    $this->assertTrue($module_installer->install(['cache_test']));
    $this->assertTrue(isset($GLOBALS['hook_module_preinstall']));
    $this->assertTrue(isset($GLOBALS['hook_modules_installed']));
    $module_installer->uninstall(['cache_test']);
    $this->assertTrue(isset($GLOBALS['hook_module_preuninstall']));
    $this->assertTrue(isset($GLOBALS['hook_modules_uninstalled']));
    $this->assertTrue(isset($GLOBALS['hook_cache_flush']));
  }

  /**
   * Tests install with a module with an invalid core version constraint.
   *
   * @dataProvider providerTestInvalidCoreInstall
   * @covers ::install
   */
  public function testInvalidCoreInstall($module_name, $install_dependencies): void {
    $this->expectException(MissingDependencyException::class);
    $this->expectExceptionMessage("Unable to install modules: module '$module_name' is incompatible with this version of Drupal core.");
    $this->container->get('module_installer')->install([$module_name], $install_dependencies);
  }

  /**
   * Data provider for testInvalidCoreInstall().
   */
  public static function providerTestInvalidCoreInstall() {
    return [
      'no dependencies system_core_incompatible_semver_test' => [
        'system_core_incompatible_semver_test',
        FALSE,
      ],
      'install_dependencies system_core_incompatible_semver_test' => [
        'system_core_incompatible_semver_test',
        TRUE,
      ],
    ];
  }

  /**
   * Tests install with a dependency with an invalid core version constraint.
   *
   * @covers ::install
   */
  public function testDependencyInvalidCoreInstall(): void {
    $this->expectException(MissingDependencyException::class);
    $this->expectExceptionMessage("Unable to install modules: module 'system_incompatible_core_version_dependencies_test'. Its dependency module 'system_core_incompatible_semver_test' is incompatible with this version of Drupal core.");
    $this->container->get('module_installer')->install(['system_incompatible_core_version_dependencies_test']);
  }

  /**
   * Tests no dependencies install with a dependency with invalid core.
   *
   * @covers ::install
   */
  public function testDependencyInvalidCoreInstallNoDependencies(): void {
    $this->assertTrue($this->container->get('module_installer')->install(['system_incompatible_core_version_dependencies_test'], FALSE));
  }

  /**
   * Tests trying to install an obsolete module.
   *
   * @covers ::install
   */
  public function testObsoleteInstall(): void {
    $this->expectException(ObsoleteExtensionException::class);
    $this->expectExceptionMessage("Unable to install modules: module 'system_status_obsolete_test' is obsolete.");
    $this->container->get('module_installer')->install(['system_status_obsolete_test']);
  }

  /**
   * Tests container rebuilding due to the container_rebuild_required info key.
   *
   * @param array $modules
   *   The modules to install.
   * @param int $count
   *   The number of times the container should have been rebuilt.
   *
   * @covers ::install
   * @dataProvider containerRebuildRequiredProvider
   */
  public function testContainerRebuildRequired(array $modules, int $count): void {
    $this->container->get('module_installer')->install(['module_test']);
    $GLOBALS['container_rebuilt'] = 0;
    $this->container->get('module_installer')->install($modules);
    $this->assertSame($count, $GLOBALS['container_rebuilt']);
  }

  /**
   * Data provider for ::testContainerRebuildRequired().
   */
  public static function containerRebuildRequiredProvider(): array {
    return [
      [['container_rebuild_required_true'], 1],
      [['container_rebuild_required_false'], 1],
      [['container_rebuild_required_false', 'container_rebuild_required_false_2'], 1],
      [['container_rebuild_required_false', 'container_rebuild_required_false_2', 'container_rebuild_required_true'], 2],
      [['container_rebuild_required_false', 'container_rebuild_required_false_2', 'container_rebuild_required_true', 'container_rebuild_required_true_2'], 3],
      [['container_rebuild_required_true', 'container_rebuild_required_false', 'container_rebuild_required_false_2'], 2],
      [['container_rebuild_required_false', 'container_rebuild_required_true', 'container_rebuild_required_false_2'], 3],
      [['container_rebuild_required_false', 'container_rebuild_required_true', 'container_rebuild_required_false_2', 'container_rebuild_required_true_2'], 4],
      [['container_rebuild_required_true', 'container_rebuild_required_false', 'container_rebuild_required_true_2', 'container_rebuild_required_false_2'], 4],
      [['container_rebuild_required_false_2', 'container_rebuild_required_dependency_false'], 3],
      [['container_rebuild_required_false_2', 'container_rebuild_required_dependency_false', 'container_rebuild_required_true'], 3],
    ];
  }

  /**
   * Tests trying to install a deprecated module.
   *
   * @covers ::install
   *
   * @group legacy
   */
  public function testDeprecatedInstall(): void {
    $this->expectDeprecation("The module 'deprecated_module' is deprecated. See http://example.com/deprecated");
    \Drupal::service('module_installer')->install(['deprecated_module']);
    $this->assertTrue(\Drupal::service('module_handler')->moduleExists('deprecated_module'));
  }

  /**
   * Tests the BC layer for uninstall validators.
   *
   * @covers ::__construct
   * @covers ::addUninstallValidator
   *
   * @group legacy
   */
  public function testUninstallValidatorsBC(): void {
    $this->expectDeprecation('The "module_installer.uninstall_validators" service is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Inject "!tagged_iterator module_install.uninstall_validator" instead. See https://www.drupal.org/node/3432595');
    $module_installer = new ModuleInstaller(
      $this->container->getParameter('app.root'),
      $this->container->get('module_handler'),
      $this->container->get('kernel'),
      $this->container->get('database'),
      $this->container->get('update.update_hook_registry'),
      $this->container->get('logger.channel.default'),
    );

    $this->expectDeprecation('Drupal\Core\Extension\ModuleInstaller::addUninstallValidator is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Inject the uninstall validators into the constructor instead. See https://www.drupal.org/node/3432595');
    $module_installer->addUninstallValidator($this->createMock(ModuleUninstallValidatorInterface::class));
  }

  /**
   * Tests field storage definitions are installed only if entity types exist.
   */
  public function testFieldStorageEntityTypeDependencies(): void {
    $profile = 'minimal';
    $this->setInstallProfile($profile);
    // Install a module that will make workspaces a dependency of taxonomy.
    \Drupal::service('module_installer')->install(['field_storage_entity_type_dependency_test']);
    // Installing taxonomy will install workspaces first. During installation of
    // workspaces, the storage for 'workspace' field should not be attempted
    // before the taxonomy term entity storage has been created, so there
    // should not be a EntityStorageException logged.
    \Drupal::service('module_installer')->install(['taxonomy']);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('workspaces'));
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('taxonomy'));
    $this->assertArrayHasKey('workspace', \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('taxonomy_term'));
  }

  /**
   * Tests that entity storage tables are installed before simple config.
   *
   * When multiple modules are installed together in one batch, entity storage
   * for entity types in all the modules should exist before simple config from
   * any module is installed.
   */
  public function testEntityStorageInstalledBeforeSimpleConfig(): void {
    \Drupal::service('module_installer')->install(['node', 'module_installer_config_subscriber']);
    // The module_installer_config_subscriber module has an config save event
    // subscriber for its own simple config. When that config is saved during
    // module installed, it checks that the node storage table exists.
    $this->assertNotTrue(\Drupal::keyValue('module_installer_config_subscriber')->get('node_tables_missing'));
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container
      ->register(__CLASS__, __CLASS__)
      ->setSynthetic(TRUE)
      ->addTag('logger');
    $container->set(__CLASS__, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, \Stringable|string $message, array $context = []): void {
    if ($level > RfcLogLevel::ERROR) {
      return;
    }

    // Fails the test if an error or more severe message is logged.
    $message = (string) $message;
    $placeholders = \Drupal::service('logger.log_message_parser')->parseMessagePlaceholders($message, $context);
    $message = empty($placeholders) ? $message : strtr($message, $placeholders);
    $this->fail($message);
  }

}
