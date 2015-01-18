<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Menu\UserLocalTasksTest.
 */

namespace Drupal\Tests\user\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests user local tasks.
 *
 * @group user
 */
class UserLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
    $this->directoryList = array('user' => 'core/modules/user');
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
    return array(
      array('user.admin_account', array(array('user.admin_account', 'user.admin_permissions', 'user.role_list'))),
      array('user.admin_permissions', array(array('user.admin_account', 'user.admin_permissions', 'user.role_list'))),
      array('user.role_list', array(array('user.admin_account', 'user.admin_permissions', 'user.role_list'))),
      array('entity.user.admin_form', array(array('user.account_settings_tab'))),
    );
  }

  /**
   * Checks user listing local tasks.
   *
   * @dataProvider getUserLoginRoutes
   */
  public function testUserLoginLocalTasks($route) {
    $tasks = array(
      0 => array('user.register', 'user.pass', 'user.login',),
    );
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getUserLoginRoutes() {
    return array(
      array('user.login'),
      array('user.register'),
      array('user.pass'),
    );
  }

  /**
   * Checks user listing local tasks.
   *
   * @dataProvider getUserPageRoutes
   */
  public function testUserPageLocalTasks($route, $subtask = array()) {
    $tasks = array(
      0 => array('entity.user.canonical', 'entity.user.edit_form',),
    );
    if ($subtask) $tasks[] = $subtask;
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getUserPageRoutes() {
    return array(
      array('entity.user.canonical'),
      array('entity.user.edit_form'),
    );
  }

}
