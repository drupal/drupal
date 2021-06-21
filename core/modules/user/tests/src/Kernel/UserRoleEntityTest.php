<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * @group user
 */
class UserRoleEntityTest extends KernelTestBase {

  protected static $modules = ['system', 'user'];

  public function testOrderOfPermissions() {
    $role = Role::create(['id' => 'test_role']);
    $role->grantPermission('b')
      ->grantPermission('a')
      ->grantPermission('c')
      ->save();
    $this->assertEquals(['a', 'b', 'c'], $role->getPermissions());

    $role->revokePermission('b')->save();
    $this->assertEquals(['a', 'c'], $role->getPermissions());

    $role->grantPermission('b')->save();
    $this->assertEquals(['a', 'b', 'c'], $role->getPermissions());
  }

}
