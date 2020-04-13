<?php

namespace Drupal\Tests\user\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests user local tasks.
 *
 * @group user
 */
class UserLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp(): void {
    $this->directoryList = ['user' => 'core/modules/user'];
    parent::setUp();
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getUserAdminRoutes
   */
  public function testUserAdminLocalTasks($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getUserAdminRoutes() {
    return [
      ['entity.user.collection', [['entity.user.collection', 'user.admin_permissions', 'entity.user_role.collection']]],
      ['user.admin_permissions', [['entity.user.collection', 'user.admin_permissions', 'entity.user_role.collection']]],
      ['entity.user_role.collection', [['entity.user.collection', 'user.admin_permissions', 'entity.user_role.collection']]],
      ['entity.user.admin_form', [['user.account_settings_tab']]],
    ];
  }

  /**
   * Checks user listing local tasks.
   *
   * @dataProvider getUserLoginRoutes
   */
  public function testUserLoginLocalTasks($route) {
    $tasks = [
      0 => ['user.register', 'user.pass', 'user.login'],
    ];
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getUserLoginRoutes() {
    return [
      ['user.login'],
      ['user.register'],
      ['user.pass'],
    ];
  }

  /**
   * Checks user listing local tasks.
   *
   * @dataProvider getUserPageRoutes
   */
  public function testUserPageLocalTasks($route, $subtask = []) {
    $tasks = [
      0 => ['entity.user.canonical', 'entity.user.edit_form'],
    ];
    if ($subtask) {
      $tasks[] = $subtask;
    }
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getUserPageRoutes() {
    return [
      ['entity.user.canonical'],
      ['entity.user.edit_form'],
    ];
  }

}
