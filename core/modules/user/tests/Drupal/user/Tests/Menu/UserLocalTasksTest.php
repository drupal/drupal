<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Menu\UserLocalTasksTest.
 */

namespace Drupal\user\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of user local tasks.
 *
 * @group Drupal
 * @group User
 */
class UserLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'User local tasks test',
      'description' => 'Test user local tasks.',
      'group' => 'User',
    );
  }

  public function setUp() {
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
      array('user.account_settings', array(array('user.account_settings_tab'))),
    );
  }

  /**
   * Checks user listing local tasks.
   *
   * @dataProvider getUserLoginRoutes
   */
  public function testUserLoginLocalTasks($route, $subtask = array()) {
    $tasks = array(
      0 => array('user.page', 'user.register', 'user.pass',),
    );
    if ($subtask) {
      $tasks[] = $subtask;
    }
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getUserLoginRoutes() {
    return array(
      array('user.page', array('user.login',)),
      array('user.login', array('user.login',)),
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
      0 => array('user.view', 'user.edit',),
    );
    if ($subtask) $tasks[] = $subtask;
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getUserPageRoutes() {
    return array(
      array('user.view'),
      array('user.edit'),
    );
  }

}
