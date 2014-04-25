<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Plugin\Action\RemoveRoleUserTest.
 */

namespace Drupal\user\Tests\Plugin\Action;

use Drupal\user\Plugin\Action\RemoveRoleUser;

/**
 * Tests the role remove plugin.
 *
 * @see \Drupal\user\Plugin\Action\RemoveRoleUser
 */
class RemoveRoleUserTest extends RoleUserTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Remove user plugin',
      'description' => 'Tests the role remove plugin',
      'group' => 'User',
    );
  }

  /**
   * Tests the execute method on a user with a role.
   */
  public function testExecuteRemoveExistingRole() {
    $this->account->expects($this->once())
      ->method('removeRole');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(TRUE));

    $config = array('rid' => 'test_role_1');
    $remove_role_plugin = new RemoveRoleUser($config, 'user_remove_role_action', array('type' => 'user'), $this->userRoleEntityType);

    $remove_role_plugin->execute($this->account);
  }

  /**
   * Tests the execute method on a user without a specific role.
   */
  public function testExecuteRemoveNonExistingRole() {
    $this->account->expects($this->never())
      ->method('removeRole');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(FALSE));

    $config = array('rid' => 'test_role_1');
    $remove_role_plugin = new RemoveRoleUser($config, 'user_remove_role_action', array('type' => 'user'), $this->userRoleEntityType);

    $remove_role_plugin->execute($this->account);
  }

}
