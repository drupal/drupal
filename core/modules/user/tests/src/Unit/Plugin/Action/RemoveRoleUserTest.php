<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Plugin\Action;

use Drupal\user\Plugin\Action\RemoveRoleUser;

/**
 * @coversDefaultClass \Drupal\user\Plugin\Action\RemoveRoleUser
 * @group user
 */
class RemoveRoleUserTest extends RoleUserTestBase {

  /**
   * Tests the execute method on a user with a role.
   */
  public function testExecuteRemoveExistingRole() {
    $this->account->expects($this->once())
      ->method('removeRole');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->willReturn(TRUE);

    $config = ['rid' => 'test_role_1'];
    $remove_role_plugin = new RemoveRoleUser($config, 'user_remove_role_action', ['type' => 'user'], $this->userRoleEntityType);

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
      ->willReturn(FALSE);

    $config = ['rid' => 'test_role_1'];
    $remove_role_plugin = new RemoveRoleUser($config, 'user_remove_role_action', ['type' => 'user'], $this->userRoleEntityType);

    $remove_role_plugin->execute($this->account);
  }

}
