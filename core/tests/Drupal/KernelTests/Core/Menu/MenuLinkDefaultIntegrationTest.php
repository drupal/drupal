<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Menu;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests integration of static menu links.
 *
 * @group Menu
 */
class MenuLinkDefaultIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_test',
  ];

  /**
   * Tests moving a static menu link without a specified menu to the root.
   */
  public function testMoveToRoot(): void {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_link_manager->rebuild();

    $menu_link = $menu_link_manager->getDefinition('menu_test.child');
    $this->assertEquals('menu_test.parent', $menu_link['parent']);
    $this->assertEquals('test', $menu_link['menu_name']);

    $tree = \Drupal::menuTree()->load('test', new MenuTreeParameters());
    $this->assertCount(1, $tree);
    $this->assertEquals('menu_test.parent', $tree['menu_test.parent']->link->getPluginId());
    $this->assertEquals('menu_test.child', $tree['menu_test.parent']->subtree['menu_test.child']->link->getPluginId());

    // Ensure that the menu name is not forgotten.
    $menu_link_manager->updateDefinition('menu_test.child', ['parent' => '']);
    $menu_link = $menu_link_manager->getDefinition('menu_test.child');

    $this->assertEquals('', $menu_link['parent']);
    $this->assertEquals('test', $menu_link['menu_name']);

    $tree = \Drupal::menuTree()->load('test', new MenuTreeParameters());
    $this->assertCount(2, $tree);
    $this->assertEquals('menu_test.parent', $tree['menu_test.parent']->link->getPluginId());
    $this->assertEquals('menu_test.child', $tree['menu_test.child']->link->getPluginId());

    $this->assertTrue(TRUE);
  }

}
