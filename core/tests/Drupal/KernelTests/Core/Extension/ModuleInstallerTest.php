<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Database\Database;
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

}
