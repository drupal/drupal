<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\Exception\ObsoleteExtensionException;
use Drupal\Core\Extension\ModuleInstaller;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Tests the ModuleInstaller class.
 *
 * @coversDefaultClass \Drupal\Core\Extension\ModuleInstaller
 *
 * @group Extension
 */
class ModuleInstallerTest extends KernelTestBase {

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

}
