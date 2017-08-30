<?php

namespace Drupal\Tests\user\Traits;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Provides methods to create additional test users and switch the currently
 * logged in one.
 *
 * This trait is meant to be used only by test classes.
 */
trait UserCreationTrait {

  /**
   * Switch the current logged in user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object.
   */
  protected function setCurrentUser(AccountInterface $account) {
    \Drupal::currentUser()->setAccount($account);
  }

  /**
   * Create a user with a given set of permissions.
   *
   * @param array $permissions
   *   Array of permission names to assign to user. Note that the user always
   *   has the default permissions derived from the "authenticated users" role.
   * @param string $name
   *   The user name.
   * @param bool $admin
   *   (optional) Whether the user should be an administrator
   *   with all the available permissions.
   *
   * @return \Drupal\user\Entity\User|false
   *   A fully loaded user object with pass_raw property, or FALSE if account
   *   creation fails.
   */
  protected function createUser(array $permissions = [], $name = NULL, $admin = FALSE) {
    // Create a role with the given permission set, if any.
    $rid = FALSE;
    if ($permissions) {
      $rid = $this->createRole($permissions);
      if (!$rid) {
        return FALSE;
      }
    }

    // Create a user assigned to that role.
    $edit = [];
    $edit['name'] = !empty($name) ? $name : $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['pass'] = user_password();
    $edit['status'] = 1;
    if ($rid) {
      $edit['roles'] = [$rid];
    }

    if ($admin) {
      $edit['roles'][] = $this->createAdminRole();
    }

    $account = User::create($edit);
    $account->save();

    $this->assertTrue($account->id(), SafeMarkup::format('User created with name %name and pass %pass', ['%name' => $edit['name'], '%pass' => $edit['pass']]), 'User login');
    if (!$account->id()) {
      return FALSE;
    }

    // Add the raw password so that we can log in as this user.
    $account->pass_raw = $edit['pass'];
    // Support BrowserTestBase as well.
    $account->passRaw = $account->pass_raw;
    return $account;
  }

  /**
   * Creates an administrative role.
   *
   * @param string $rid
   *   (optional) The role ID (machine name). Defaults to a random name.
   * @param string $name
   *   (optional) The label for the role. Defaults to a random string.
   * @param int $weight
   *   (optional) The weight for the role. Defaults NULL so that entity_create()
   *   sets the weight to maximum + 1.
   *
   * @return string
   *   Role ID of newly created role, or FALSE if role creation failed.
   */
  protected function createAdminRole($rid = NULL, $name = NULL, $weight = NULL) {
    $rid = $this->createRole([], $rid, $name, $weight);
    if ($rid) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = Role::load($rid);
      $role->setIsAdmin(TRUE);
      $role->save();
    }
    return $rid;
  }

  /**
   * Creates a role with specified permissions.
   *
   * @param array $permissions
   *   Array of permission names to assign to role.
   * @param string $rid
   *   (optional) The role ID (machine name). Defaults to a random name.
   * @param string $name
   *   (optional) The label for the role. Defaults to a random string.
   * @param int $weight
   *   (optional) The weight for the role. Defaults NULL so that entity_create()
   *   sets the weight to maximum + 1.
   *
   * @return string
   *   Role ID of newly created role, or FALSE if role creation failed.
   */
  protected function createRole(array $permissions, $rid = NULL, $name = NULL, $weight = NULL) {
    // Generate a random, lowercase machine name if none was passed.
    if (!isset($rid)) {
      $rid = strtolower($this->randomMachineName(8));
    }
    // Generate a random label.
    if (!isset($name)) {
      // In the role UI role names are trimmed and random string can start or
      // end with a space.
      $name = trim($this->randomString(8));
    }

    // Check the all the permissions strings are valid.
    if (!$this->checkPermissions($permissions)) {
      return FALSE;
    }

    // Create new role.
    $role = Role::create([
      'id' => $rid,
      'label' => $name,
    ]);
    if (isset($weight)) {
      $role->set('weight', $weight);
    }
    $result = $role->save();

    $this->assertIdentical($result, SAVED_NEW, SafeMarkup::format('Created role ID @rid with name @name.', [
      '@name' => var_export($role->label(), TRUE),
      '@rid' => var_export($role->id(), TRUE),
    ]), 'Role');

    if ($result === SAVED_NEW) {
      // Grant the specified permissions to the role, if any.
      if (!empty($permissions)) {
        $this->grantPermissions($role, $permissions);
        $assigned_permissions = Role::load($role->id())->getPermissions();
        $missing_permissions = array_diff($permissions, $assigned_permissions);
        if (!$missing_permissions) {
          $this->pass(SafeMarkup::format('Created permissions: @perms', ['@perms' => implode(', ', $permissions)]), 'Role');
        }
        else {
          $this->fail(SafeMarkup::format('Failed to create permissions: @perms', ['@perms' => implode(', ', $missing_permissions)]), 'Role');
        }
      }
      return $role->id();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Checks whether a given list of permission names is valid.
   *
   * @param array $permissions
   *   The permission names to check.
   *
   * @return bool
   *   TRUE if the permissions are valid, FALSE otherwise.
   */
  protected function checkPermissions(array $permissions) {
    $available = array_keys(\Drupal::service('user.permissions')->getPermissions());
    $valid = TRUE;
    foreach ($permissions as $permission) {
      if (!in_array($permission, $available)) {
        $this->fail(SafeMarkup::format('Invalid permission %permission.', ['%permission' => $permission]), 'Role');
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * Grant permissions to a user role.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The ID of a user role to alter.
   * @param array $permissions
   *   (optional) A list of permission names to grant.
   */
  protected function grantPermissions(RoleInterface $role, array $permissions) {
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->trustData()->save();
  }

}
