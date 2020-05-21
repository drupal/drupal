<?php

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\Component\Utility\Html;
use Drupal\views\Views;

/**
 * Tests the permissions filter handler.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\filter\Permissions
 */
class HandlerFilterPermissionTest extends UserKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_permission'];

  protected $columnMap;

  /**
   * Tests the permission filter handler.
   *
   * @todo Fix the different commented out tests by fixing the many to one
   *   handler handling with the NOT operator.
   */
  public function testFilterPermission() {
    $this->setupPermissionTestData();

    $column_map = ['uid' => 'uid'];
    $view = Views::getView('test_filter_permission');

    // Filter by a non existing permission.
    $view->initHandlers();
    $view->filter['permission']->value = ['non_existent_permission'];
    $this->executeView($view);
    $this->assertCount(4, $view->result, 'A non existent permission is not filtered so everything is the result.');
    $expected[] = ['uid' => 1];
    $expected[] = ['uid' => 2];
    $expected[] = ['uid' => 3];
    $expected[] = ['uid' => 4];
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    // Filter by a permission.
    $view->initHandlers();
    $view->filter['permission']->value = ['administer permissions'];
    $this->executeView($view);
    $this->assertCount(2, $view->result);
    $expected = [];
    $expected[] = ['uid' => 3];
    $expected[] = ['uid' => 4];
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    // Filter by not a permission.
    $view->initHandlers();
    $view->filter['permission']->operator = 'not';
    $view->filter['permission']->value = ['administer users'];
    $this->executeView($view);
    $this->assertCount(3, $view->result);
    $expected = [];
    $expected[] = ['uid' => 1];
    $expected[] = ['uid' => 2];
    $expected[] = ['uid' => 3];
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    // Filter by not multiple permissions, that are present in multiple roles.
    $view->initHandlers();
    $view->filter['permission']->operator = 'not';
    $view->filter['permission']->value = ['administer users', 'administer permissions'];
    $this->executeView($view);
    $this->assertCount(2, $view->result);
    $expected = [];
    $expected[] = ['uid' => 1];
    $expected[] = ['uid' => 2];
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    // Filter by another permission of a role with multiple permissions.
    $view->initHandlers();
    $view->filter['permission']->value = ['administer users'];
    $this->executeView($view);
    $this->assertCount(1, $view->result);
    $expected = [];
    $expected[] = ['uid' => 4];
    $this->assertIdenticalResultset($view, $expected, $column_map);
    $view->destroy();

    $view->initDisplay();
    $view->initHandlers();

    // Test the value options.
    $value_options = $view->filter['permission']->getValueOptions();

    $permission_by_module = [];
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    foreach ($permissions as $name => $permission) {
      $permission_by_module[$permission['provider']][$name] = $permission;
    }
    foreach (['system' => 'System', 'user' => 'User'] as $module => $title) {
      $expected = array_map(function ($permission) {
        return Html::escape(strip_tags($permission['title']));
      }, $permission_by_module[$module]);

      $this->assertEqual($expected, $value_options[$title], 'Ensure the all permissions are available');
    }
  }

}
