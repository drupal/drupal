<?php

namespace Drupal\Tests\system\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Tests\UnitTestCase;
use Drupal\user\PermissionHandlerInterface;

/**
 * @group system
 */
class SystemAdminModuleTasksTest extends UnitTestCase {

  /**
   * @group legacy
   * @expectedDeprecation system_get_module_admin_tasks() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\system\AdminHelperTrait::getModuleAdminTasks() instead. See https://www.drupal.org/node/3038972
   * @see system_get_module_admin_tasks()
   */
  public function testSystemGetModuleAdminTasksDeprecation() {
    $container = new ContainerBuilder();
    $menu_link_tree = $this->prophesize(MenuLinkTreeInterface::class);
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('system.admin')->excludeRoot()->onlyEnabledLinks();
    $menu_link_tree->load("system.admin", $parameters)->willReturn([]);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'menu.default_tree_manipulators:flatten'],
    ];
    $menu_link_tree->transform([], $manipulators)->willReturn([]);
    $container->set('menu.link_tree', $menu_link_tree->reveal());
    $user_permissions = $this->prophesize(PermissionHandlerInterface::class);
    $container->set('user.permissions', $user_permissions->reveal());
    \Drupal::setContainer($container);

    require_once $this->root . '/core/modules/system/system.module';
    system_get_module_admin_tasks('foo', []);
  }

  /**
   * @group legacy
   * @expectedDeprecation Using drupal_static_reset() with 'system_get_module_admin_tasks' is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\system\AdminHelperTrait::resetModuleAdminTasksCache() instead. See https://www.drupal.org/node/3038972
   * @see drupal_static_reset()
   */
  public function testSystemGetModuleAdminTasksResetCacheDeprecation() {
    require_once $this->root . '/core/includes/bootstrap.inc';
    drupal_static_reset('system_get_module_admin_tasks');
  }

}
