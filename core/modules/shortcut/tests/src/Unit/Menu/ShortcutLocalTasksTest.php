<?php

namespace Drupal\Tests\shortcut\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of shortcut local tasks.
 *
 * @group shortcut
 */
class ShortcutLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp(): void {
    $this->directoryList = [
      'shortcut' => 'core/modules/shortcut',
      'user' => 'core/modules/user',
    ];
    parent::setUp();
  }

  /**
   * Checks shortcut listing local tasks.
   *
   * @dataProvider getShortcutPageRoutes
   */
  public function testShortcutPageLocalTasks($route) {
    $tasks = [
      0 => ['shortcut.set_switch', 'entity.user.canonical', 'entity.user.edit_form'],
    ];
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getShortcutPageRoutes() {
    return [
      ['entity.user.canonical'],
      ['entity.user.edit_form'],
      ['shortcut.set_switch'],
    ];
  }

}
