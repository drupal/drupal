<?php

/**
 * @file
 * Contains \Drupal\Tests\shortcut\Unit\Menu\ShortcutLocalTasksTest.
 */

namespace Drupal\Tests\shortcut\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of shortcut local tasks.
 *
 * @group shortcut
 */
class ShortcutLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
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
      0 => array('shortcut.set_switch', 'entity.user.canonical', 'entity.user.edit_form',),
    );
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getShortcutPageRoutes() {
    return array(
      array('entity.user.canonical'),
      array('entity.user.edit_form'),
      array('shortcut.set_switch'),
    );
  }

}
