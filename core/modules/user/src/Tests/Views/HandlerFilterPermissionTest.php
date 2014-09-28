<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\HandlerFilterPermissionTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\Component\Utility\String;
use Drupal\user\Tests\Views\UserUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the permissions filter handler.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\filter\Permissions
 */
class HandlerFilterPermissionTest extends UserUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_permission');

  protected $columnMap;

  /**
   * Tests the permission filter handler.
   *
   * @todo Fix the different commented out tests by fixing the many to one
   *   handler handling with the NOT operator.
   */
  public function testFilterPermission() {
    $this->setupPermissionTestData();

    $column_map = array('uid' => 'uid');
    $view = Views::getView('test_filter_permission');

    // Filter by a non existing permission.
    $view->initHandlers();
    $view->filter['permission']->value = array('non_existent_permission');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 4, 'A non existent permission is not filtered so everything is the result.');
    $expected[] = array('uid' => 1);
    $expected[] = array('uid' => 2);
    $expected[] = array('uid' => 3);
    $expected[] = array('uid' => 4);
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    // Filter by a permission.
    $view->initHandlers();
    $view->filter['permission']->value = array('administer permissions');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 2);
    $expected = array();
    $expected[] = array('uid' => 3);
    $expected[] = array('uid' => 4);
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    // Filter by another permission of a role with multiple permissions.
    $view->initHandlers();
    $view->filter['permission']->value = array('administer users');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 1);
    $expected = array();
    $expected[] = array('uid' => 4);
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    $view->initDisplay();
    $view->initHandlers();
    $view->filter['permission']->getValueOptions();

    // Test the value options.
    $value_options = $view->filter['permission']->getValueOptions();

    $permission_by_module = [];
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    foreach ($permissions as $name => $permission) {
      $permission_by_module[$permission['provider']][$name] = $permission;
    }
    foreach (array('system' => 'System', 'user' => 'User') as $module => $title) {
      $expected = array_map(function ($permission) {
        return String::checkPlain(strip_tags($permission['title']));
      }, $permission_by_module[$module]);

      $this->assertEqual($expected, $value_options[$title], 'Ensure the all permissions are available');
    }
  }

}
