<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * @group user
 * @coversDefaultClass \Drupal\user\Entity\Role
 */
class UserRoleEntityTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'user_permissions_test'];

  public function testOrderOfPermissions() {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
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

  public function testGrantingNonExistentPermission() {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);

    // A single permission that does not exist.
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Adding non-existent permissions to a role is not allowed. The incorrect permissions are "does not exist".');
    $role->grantPermission('does not exist')
      ->save();

    // A multiple permissions that do not exist.
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Adding non-existent permissions to a role is not allowed. The incorrect permissions are "does not exist, also does not exist".');
    $role->grantPermission('does not exist')
      ->grantPermission('also does not exist')
      ->save();
  }

  public function testPermissionRevokeAndConfigSync() {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
    $role->setSyncing(TRUE);
    $role->grantPermission('a')
      ->grantPermission('b')
      ->grantPermission('c')
      ->save();
    $this->assertSame(['a', 'b', 'c'], $role->getPermissions());
    $role->revokePermission('b')->save();
    $this->assertSame(['a', 'c'], $role->getPermissions());
  }

}
