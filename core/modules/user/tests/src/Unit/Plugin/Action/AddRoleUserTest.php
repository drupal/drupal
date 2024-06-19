<?php

declare(strict_types=1);

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
  public function testExecuteAddExistingRole(): void {
    $this->account->expects($this->never())
      ->method('addRole')
      ->willReturn($this->account);

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->willReturn(TRUE);

    $config = ['rid' => 'test_role_1'];
    $add_role_plugin = new AddRoleUser($config, 'user_add_role_action', ['type' => 'user'], $this->userRoleEntityType);

    $add_role_plugin->execute($this->account);
  }

  /**
   * Tests the execute method on a user without a specific role.
   */
  public function testExecuteAddNonExistingRole(): void {
    $this->account->expects($this->once())
      ->method('addRole')
      ->willReturn($this->account);

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->willReturn(FALSE);

    $config = ['rid' => 'test_role_1'];
    $add_role_plugin = new AddRoleUser($config, 'user_add_role_action', ['type' => 'user'], $this->userRoleEntityType);

    $add_role_plugin->execute($this->account);
  }

}
