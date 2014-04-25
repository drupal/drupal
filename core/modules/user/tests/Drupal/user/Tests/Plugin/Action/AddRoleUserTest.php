<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Plugin\Action\AddRoleUserTest.
 */

namespace Drupal\user\Tests\Plugin\Action;

use Drupal\user\Plugin\Action\AddRoleUser;

/**
 * Tests the role add plugin.
 *
 * @see \Drupal\user\Plugin\Action\AddRoleUser
 */
class AddRoleUserTest extends RoleUserTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Add user plugin',
      'description' => 'Tests the role add plugin',
      'group' => 'User',
    );
  }

  /**
   * Tests the execute method on a user with a role.
   */
  public function testExecuteAddExistingRole() {
    $this->account->expects($this->never())
      ->method('addRole');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(TRUE));

    $config = array('rid' => 'test_role_1');
    $remove_role_plugin = new AddRoleUser($config, 'user_add_role_action', array('type' => 'user'), $this->userRoleEntityType);

    $remove_role_plugin->execute($this->account);
  }

  /**
   * Tests the execute method on a user without a specific role.
   */
  public function testExecuteAddNonExistingRole() {
    $this->account->expects($this->once())
      ->method('addRole');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(FALSE));

    $config = array('rid' => 'test_role_1');
    $remove_role_plugin = new AddRoleUser($config, 'user_remove_role_action', array('type' => 'user'), $this->userRoleEntityType);

    $remove_role_plugin->execute($this->account);
  }

}
