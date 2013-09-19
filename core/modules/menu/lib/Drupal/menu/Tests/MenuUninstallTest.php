<?php

/**
 * @file
 * Contains \Drupal\menu\Tests\MenuUninstallTest.
 */

namespace Drupal\menu\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that uninstalling menu does not remove custom menus.
 */
class MenuUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu');

  public static function getInfo() {
    return array(
      'name' => 'Uninstall menu test',
      'description' => 'Tests that uninstalling menu does not remove custom menus.',
      'group' => 'Menu',
    );
  }

  /**
   * Tests Menu uninstall.
   */
  public function testMenuUninstall() {
    \Drupal::moduleHandler()->uninstall(array('menu'));

    $this->assertTrue(entity_load('menu', 'admin', TRUE), 'The \'admin\' menu still exists after uninstalling menu module.');
  }

}
