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
 * @group Drupal
 * @group Shortcut
 */
class ShortcutLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Shortcut local tasks test',
      'description' => 'Test shortcut local tasks.',
      'group' => 'Shortcut',
    );
  }

  public function setUp() {
    $this->moduleList = array(
      'shortcut' => 'core/modules/shortcut/shortcut.module',
      'user' => 'core/modules/user/user.module',
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
      0 => array('shortcut.overview', 'user.view', 'user.edit',),
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
      array('shortcut.overview'),
    );
  }

}
