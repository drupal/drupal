<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\MissingDependencyException;
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
   * Modules to install.
   *
   * The System module is required because system_rebuild_module_data() is used.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * Tests that routes are rebuilt during install and uninstall of modules.
   *
   * @covers ::install
   * @covers ::uninstall
   */
  public function testRouteRebuild() {
    // Remove the routing table manually to ensure it can be created lazily
    // properly.
    Database::getConnection()->schema()->dropTable('router');

    $this->container->get('module_installer')->install(['router_test']);
    $route = $this->container->get('router.route_provider')->getRouteByName('router_test.1');
    $this->assertEquals('/router_test/test1', $route->getPath());

    $this->container->get('module_installer')->uninstall(['router_test']);
    $this->setExpectedException(RouteNotFoundException::class);
    $this->container->get('router.route_provider')->getRouteByName('router_test.1');
  }

  /**
   * Tests config changes by hook_install() are saved for dependent modules.
   *
   * @covers ::install
   */
  public function testConfigChangeOnInstall() {
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
  public function testCacheBinCleanup() {
    $schema = $this->container->get('database')->schema();
    $table = 'cache_module_cachebin';

    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['module_cachebin']);

    // Prime the bin.
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache_bin */
    $cache_bin = $this->container->get('module_cachebin.cache_bin');
    $cache_bin->set('foo', 'bar');

    // A database backend is used so there is a convenient way check whether the
    // backend is uninstalled.
    $this->assertTrue($schema->tableExists($table));

    $module_installer->uninstall(['module_cachebin']);
    $this->assertFalse($schema->tableExists($table));
  }

  /**
   * Ensure that rebuilding the container in hook_install() works.
   */
  public function testKernelRebuildDuringHookInstall() {
    \Drupal::state()->set('module_test_install:rebuild_container', TRUE);
    $module_installer = $this->container->get('module_installer');
    $this->assertTrue($module_installer->install(['module_test']));
  }

  /**
   * Tests install with a module with an invalid core version constraint.
   *
   * @dataProvider providerTestInvalidCoreInstall
   * @covers ::install
   */
  public function testInvalidCoreInstall($module_name, $install_dependencies) {
    $this->setExpectedException(MissingDependencyException::class, "Unable to install modules: module '$module_name' is incompatible with this version of Drupal core.");
    $this->container->get('module_installer')->install([$module_name], $install_dependencies);
  }

  /**
   * Dataprovider for testInvalidCoreInstall().
   */
  public function providerTestInvalidCoreInstall() {
    return [
      'no dependencies system_incompatible_core_version_test_1x' => [
        'system_incompatible_core_version_test_1x',
        FALSE,
      ],
      'install_dependencies system_incompatible_core_version_test_1x' => [
        'system_incompatible_core_version_test_1x',
        TRUE,
      ],
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
  public function testDependencyInvalidCoreInstall() {
    $this->setExpectedException(MissingDependencyException::class, "Unable to install modules: module 'system_incompatible_core_version_dependencies_test'. Its dependency module 'system_incompatible_core_version_test' is incompatible with this version of Drupal core.");
    $this->container->get('module_installer')->install(['system_incompatible_core_version_dependencies_test']);
  }

  /**
   * Tests no dependencies install with a dependency with invalid core.
   *
   * @covers ::install
   */
  public function testDependencyInvalidCoreInstallNoDependencies() {
    $this->assertTrue($this->container->get('module_installer')->install(['system_incompatible_core_version_dependencies_test'], FALSE));
  }

}
