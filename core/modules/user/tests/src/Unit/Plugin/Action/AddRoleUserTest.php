<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Plugin\Action\AddRoleUserTest.
 */

namespace Drupal\Tests\user\Unit\Plugin\Action;

use Drupal\user\Plugin\Action\AddRoleUser;

/**
 * @coversDefaultClass \Drupal\user\Plugin\Action\AddRoleUser
 * @group user
 */
class AddRoleUserTest extends RoleUserTestBase {

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
