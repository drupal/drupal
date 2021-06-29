<?php

namespace Drupal\KernelTests\Core\Menu;

use Drupal\KernelTests\KernelTestBase;

/**
 * Deprecation test cases for the menu layer.
 *
 * @group legacy
 */
class MenuLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Tests deprecation of the menu_list_system_menus() function.
   */
  public function testListSystemMenus(): void {
    $this->expectDeprecation('menu_list_system_menus() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\system\Entity\Menu::loadMultiple() instead. See https://www.drupal.org/node/3027453');
    $this->assertSame([
      'tools' => 'Tools',
      'admin' => 'Administration',
      'account' => 'User account menu',
      'main' => 'Main navigation',
      'footer' => 'Footer menu',
    ], menu_list_system_menus());
  }

  /**
   * Tests deprecation of the menu_ui_get_menus() function.
   */
  public function testMenuUiGetMenus(): void {
    $this->container->get('module_installer')->install(['menu_ui']);
    $this->expectDeprecation('menu_ui_get_menus() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\system\Entity\Menu::loadMultiple() instead. See https://www.drupal.org/node/3027453');
    $this->assertSame([], menu_ui_get_menus());
  }

}
