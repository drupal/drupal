<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\MenuUninstallTest.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\simpletest\WebTestBase;

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
    \Drupal::moduleHandler()->uninstall(array('menu_ui'));

    $this->assertTrue(entity_load('menu', 'admin', TRUE), 'The \'admin\' menu still exists after uninstalling Menu UI module.');
  }

}
