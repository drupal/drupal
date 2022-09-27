<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
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

  /**
   * {@inheritdoc}
   */
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
    $role_storage->create(['id' => 'test_role_one', 'label' => 'Test role 1'])->save();
    $role_storage->create(['id' => 'test_role_two', 'label' => 'Test role 2'])->save();

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
    $role_storage->create(['id' => 'test_role_one', 'label' => 'Test role 1'])->save();

    // Load user again from the database.
    $user = User::load($user->id());

    // Check that user does not have role one.
    $this->assertFalse($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));

  }

  /**
   * Tests the removal of user role dependencies.
   */
  public function testDependenciesRemoval() {
    $this->enableModules(['node', 'filter']);
    /** @var \Drupal\user\RoleStorage $role_storage */
    $role_storage = $this->container->get('entity_type.manager')->getStorage('user_role');

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create([
      'id' => 'test_role',
      'label' => $this->randomString(),
    ]);
    $role->save();

    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $node_type->save();
    // Create a new text format to be used by role $role.
    $format = FilterFormat::create([
      'format' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $format->save();

    $permission_format = "use text format {$format->id()}";
    // Add two permissions with the same dependency to ensure both are removed
    // and the role is not deleted.
    $permission_node_type = "edit any {$node_type->id()} content";
    $permission_node_type_create = "create {$node_type->id()} content";

    // Grant $role permission to access content, use $format, edit $node_type.
    $role
      ->grantPermission('access content')
      ->grantPermission($permission_format)
      ->grantPermission($permission_node_type)
      ->grantPermission($permission_node_type_create)
      ->save();

    // The role $role has the permissions to use $format and edit $node_type.
    $role_storage->resetCache();
    $role = Role::load($role->id());
    $this->assertTrue($role->hasPermission($permission_format));
    $this->assertTrue($role->hasPermission($permission_node_type));
    $this->assertTrue($role->hasPermission($permission_node_type_create));

    // Remove the format.
    $format->delete();

    // The $role config entity exists after removing the config dependency.
    $role_storage->resetCache();
    $role = Role::load($role->id());
    $this->assertNotNull($role);
    // The $format permission should have been revoked.
    $this->assertFalse($role->hasPermission($permission_format));
    $this->assertTrue($role->hasPermission($permission_node_type));
    $this->assertTrue($role->hasPermission($permission_node_type_create));

    // We have to manually trigger the removal of configuration belonging to the
    // module because KernelTestBase::disableModules() is not aware of this.
    $this->container->get('config.manager')->uninstall('module', 'node');
    // Disable the node module.
    $this->disableModules(['node']);

    // The $role config entity exists after removing the module dependency.
    $role_storage->resetCache();
    $role = Role::load($role->id());
    $this->assertNotNull($role);
    // The $node_type permission should have been revoked too.
    $this->assertFalse($role->hasPermission($permission_format));
    $this->assertFalse($role->hasPermission($permission_node_type));
    $this->assertFalse($role->hasPermission($permission_node_type_create));
    // The 'access content' permission should not have been revoked.
    $this->assertTrue($role->hasPermission('access content'));
  }

}
