<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\HandlerFilterRolesTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;

/**
 * Tests the roles filter handler.
 *
 * @group user
 *
 * @see \Drupal\user\Plugin\views\filter\Roles
 */
class HandlerFilterRolesTest extends UserKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_user_name');

  /**
   * Tests that role filter dependencies are calculated correctly.
   */
  public function testDependencies() {
    $role = Role::create(['id' => 'test_user_role']);
    $role->save();
    $view = View::load('test_user_name');
    $expected = [
      'module' => ['user'],
    ];
    $this->assertEqual($expected, $view->getDependencies());

    $display = &$view->getDisplay('default');
    $display['display_options']['filters']['roles_target_id'] = [
      'id' => 'roles_target_id',
      'table' => 'user__roles',
      'field' => 'roles_target_id',
      'value' => [
        'test_user_role' => 'test_user_role',
      ],
      'plugin_id' => 'user_roles',
    ];
    $view->save();
    $expected['config'][] = 'user.role.test_user_role';
    $this->assertEqual($expected, $view->getDependencies());

    $view = View::load('test_user_name');
    $display = &$view->getDisplay('default');
    $display['display_options']['filters']['roles_target_id'] = [
      'id' => 'roles_target_id',
      'table' => 'user__roles',
      'field' => 'roles_target_id',
      'value' => [],
      'plugin_id' => 'user_roles',
    ];
    $view->save();
    unset($expected['config']);
    $this->assertEqual($expected, $view->getDependencies());
  }

}
