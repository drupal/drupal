<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\Menu\ShortcutLocalTasksTest.
 */

namespace Drupal\shortcut\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of shortcut local tasks.
 *
 * @group shortcut
 */
class ShortcutLocalTasksTest extends LocalTaskIntegrationTest {

  public function setUp() {
    $this->directoryList = array(
      'shortcut' => 'core/modules/shortcut',
      'user' => 'core/modules/user',
    );
    parent::setUp();
  }

  /**
   * Checks shortcut listing local tasks.
   *
   * @dataProvider getShortcutPageRoutes
   */
  public function testShortcutPageLocalTasks($route) {
    $tasks = array(
      0 => array('shortcut.set_switch', 'user.view', 'user.edit',),
    );
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getShortcutPageRoutes() {
    return array(
      array('user.view'),
      array('user.edit'),
      array('shortcut.set_switch'),
    );
  }

}
