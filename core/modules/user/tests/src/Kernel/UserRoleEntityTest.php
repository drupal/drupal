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

  /**
   * @group legacy
   */
  public function testGrantingNonExistentPermission() {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);

    // A single permission that does not exist.
    $this->expectDeprecation('Adding non-existent permissions to a role is deprecated in drupal:9.3.0 and triggers a runtime exception before drupal:10.0.0. The incorrect permissions are "does not exist". Permissions should be defined in a permissions.yml file or a permission callback. See https://www.drupal.org/node/3193348');
    $role->grantPermission('does not exist')
      ->save();

    // A multiple permissions that do not exist.
    $this->expectDeprecation('Adding non-existent permissions to a role is deprecated in drupal:9.3.0 and triggers a runtime exception before drupal:10.0.0. The incorrect permissions are "does not exist", "also does not exist". Permissions should be defined in a permissions.yml file or a permission callback. See https://www.drupal.org/node/3193348');
    $role->grantPermission('does not exist')
      ->grantPermission('also does not exist')
      ->save();
  }

}
