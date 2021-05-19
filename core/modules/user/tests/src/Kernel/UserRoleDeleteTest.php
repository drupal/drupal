<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the handling of user_role entity from the user module.
 *
 * @group user
 */
class UserRoleDeleteTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'field'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests removal of role references on role entity delete.
   *
   * @see user_user_role_delete()
   */
  public function testRoleDeleteUserRoleReferenceDelete() {
    // Create two test roles.
    $role_storage = $this->container->get('entity_type.manager')->getStorage('user_role');
    $role_storage->create(['id' => 'test_role_one'])->save();
    $role_storage->create(['id' => 'test_role_two'])->save();

    // Create user and assign both test roles.
    $values = [
      'uid' => 1,
      'name' => $this->randomString(),
      'roles' => ['test_role_one', 'test_role_two'],
    ];
    $user = User::create($values);
    $user->save();

    // Check that user has both roles.
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));

    // Delete test role one.
    $test_role_one = $role_storage->load('test_role_one');
    $test_role_one->delete();

    // Load user again from the database.
    $user = User::load($user->id());

    // Check that user does not have role one anymore, still has role two.
    $this->assertFalse($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));

    // Create new role with same name.
    $role_storage->create(['id' => 'test_role_one'])->save();

    // Load user again from the database.
    $user = User::load($user->id());

    // Check that user does not have role one.
    $this->assertFalse($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));

  }

}
