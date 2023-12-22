<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ExtensionLifecycle;
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
  protected static $modules = ['system', 'user', 'path_alias'];

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
  protected function setUp(): void {
    parent::setUp();
    $this->routeProvider = \Drupal::service('router.route_provider');
    $this->moduleInfo = \Drupal::service('extension.list.module')->getList();
  }

  /**
   * Tests if the module configure routes exists.
   *
   * @dataProvider coreModuleListDataProvider
   */
  public function testModuleConfigureRoutes(string $module_name): void {
    $module_info = $this->moduleInfo[$module_name]->info;
    if (!isset($module_info['configure'])) {
      $this->markTestSkipped("$module_name has no configure route");
    }
    $module_lifecycle = $module_info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER];
    if (isset($module_lifecycle) && $module_lifecycle === ExtensionLifecycle::DEPRECATED) {
      $this->markTestSkipped("$module_name is $module_lifecycle");
    }
    $this->container->get('module_installer')->install([$module_name]);
    $this->assertModuleConfigureRoutesExist($module_name, $module_info);
  }

  /**
   * Tests if the module with lifecycle deprecated configure routes exists.
   *
   * Note: This test is part of group legacy, to make sure installing the
   * deprecated module doesn't trigger a deprecation notice.
   *
   * @group legacy
   *
   * @dataProvider coreModuleListDataProvider
   */
  public function testDeprecatedModuleConfigureRoutes(string $module_name): void {
    $module_info = $this->moduleInfo[$module_name]->info;
    if (!isset($module_info['configure'])) {
      $this->markTestSkipped("$module_name has no configure route");
    }
    $module_lifecycle = $module_info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER];
    if (isset($module_lifecycle) && $module_lifecycle !== ExtensionLifecycle::DEPRECATED) {
      $this->markTestSkipped("$module_name is not $module_lifecycle");
    }
    $this->container->get('module_installer')->install([$module_name]);
    $this->assertModuleConfigureRoutesExist($module_name, $module_info);
  }

  /**
   * Asserts the configure routes of a module exist.
   *
   * @param string $module_name
   *   The name of the module.
   * @param array $module_info
   *   An array module info.
   *
   * @internal
   */
  protected function assertModuleConfigureRoutesExist(string $module_name, array $module_info): void {
    $route = $this->routeProvider->getRouteByName($module_info['configure']);
    $this->assertNotEmpty($route, sprintf('The configure route for the "%s" module was found.', $module_name));
  }

}
