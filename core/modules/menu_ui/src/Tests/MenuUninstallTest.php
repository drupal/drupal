<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\MenuUninstallTest.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\system\Entity\Menu;

/**
 * Tests that uninstalling menu does not remove custom menus.
 *
 * @group menu_ui
 */
class MenuUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_ui');

  /**
   * Tests Menu uninstall.
   */
  public function testMenuUninstall() {
    \Drupal::service('module_installer')->uninstall(array('menu_ui'));

    \Drupal::entityManager()->getStorage('menu')->resetCache(array('admin'));

    $this->assertTrue(Menu::load('admin'), 'The \'admin\' menu still exists after uninstalling Menu UI module.');
  }

}
