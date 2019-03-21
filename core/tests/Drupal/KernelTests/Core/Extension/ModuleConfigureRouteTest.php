<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the configure route for core modules.
 *
 * @group #slow
 * @group Module
 */
class ModuleConfigureRouteTest extends KernelTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user'];

  /**
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * An array of module info.
   *
   * @var array
   */
  protected $moduleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->routeProvider = \Drupal::service('router.route_provider');
    $this->moduleInfo = system_rebuild_module_data();
  }

  /**
   * Test the module configure routes exist.
   *
   * @dataProvider coreModuleListDataProvider
   */
  public function testModuleConfigureRoutes($module) {
    $module_info = $this->moduleInfo[$module]->info;
    if (isset($module_info['configure'])) {
      $this->container->get('module_installer')->install([$module]);
      $route = $this->routeProvider->getRouteByName($module_info['configure']);
      $this->assertNotEmpty($route, sprintf('The configure route for the "%s" module was found.', $module));
    }
  }

}
