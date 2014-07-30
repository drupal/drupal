<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\MenuLinkDefaultIntegrationTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests integration of static menu links.
 *
 * @group Menu
 */
class MenuLinkDefaultIntegrationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'system',
    'menu_test',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));
  }

  /**
   * Tests moving a static menu link without a specified menu to the root.
   */
  public function testMoveToRoot() {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_link_manager->rebuild();

    $menu_link = $menu_link_manager->getDefinition('menu_test.child');
    $this->assertEqual($menu_link['parent'], 'menu_test.parent');
    $this->assertEqual($menu_link['menu_name'], 'test');

    $tree = \Drupal::menuTree()->load('test', new MenuTreeParameters());
    $this->assertEqual(count($tree), 1);
    $this->assertEqual($tree['menu_test.parent']->link->getPluginId(), 'menu_test.parent');
    $this->assertEqual($tree['menu_test.parent']->subtree['menu_test.child']->link->getPluginId(), 'menu_test.child');

    // Ensure that the menu name is not forgotten.
    $menu_link_manager->updateDefinition('menu_test.child', array('parent' => ''));
    $menu_link = $menu_link_manager->getDefinition('menu_test.child');

    $this->assertEqual($menu_link['parent'], '');
    $this->assertEqual($menu_link['menu_name'], 'test');

    $tree = \Drupal::menuTree()->load('test', new MenuTreeParameters());
    $this->assertEqual(count($tree), 2);
    $this->assertEqual($tree['menu_test.parent']->link->getPluginId(), 'menu_test.parent');
    $this->assertEqual($tree['menu_test.child']->link->getPluginId(), 'menu_test.child');

    $this->assertTrue(TRUE);
  }

}
