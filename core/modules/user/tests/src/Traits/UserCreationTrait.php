<?php

namespace Drupal\Tests\user\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
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
   * Creates a random user account and sets it as current user.
   *
   * Unless explicitly specified by setting the user ID to 1, a regular user
   * account will be created and set as current, after creating user account 1.
   * Additionally, this will ensure that at least the anonymous user account
   * exists regardless of the specified user ID.
   *
   * @param array $values
   *   (optional) An array of initial user field values.
   * @param array $permissions
   *   (optional) Array of permission names to assign to user. Note that the
   *   user always has the default permissions derived from the "authenticated
   *   users" role.
   * @param bool $admin
   *   (optional) Whether the user should be an administrator with all the
   *   available permissions.
   *
   * @return \Drupal\user\UserInterface
   *   A user account object.
   *
   * @throws \LogicException
   *   If attempting to assign additional roles to the anonymous user account.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the user could not be saved.
   */
  protected function setUpCurrentUser(array $values = [], array $permissions = [], $admin = FALSE) {
    $values += [
      'name' => $this->randomMachineName(),
    ];

    // In many cases the anonymous user account is fine for testing purposes,
    // however, if we need to create a user with a non-empty ID, we need also
    // the "sequences" table.
    if (!\Drupal::moduleHandler()->moduleExists('system')) {
      $values['uid'] = 0;
    }
    if ($this instanceof KernelTestBase && (!isset($values['uid']) || $values['uid'])) {
      try {
        $this->installSchema('system', ['sequences']);
      }
      catch (SchemaObjectExistsException $e) {
      }
    }

    // Creating an administrator or assigning custom permissions would result in
    // creating and assigning a new role to the user. This is not possible with
    // the anonymous user account.
    if (($admin || $permissions) && isset($values['uid']) && is_numeric($values['uid']) && $values['uid'] == 0) {
      throw new \LogicException('The anonymous user account cannot have additional roles.');
    }

    $original_permissions = $permissions;
    $original_admin = $admin;
    $original_values = $values;
    $autocreate_user_1 = !isset($values['uid']) || $values['uid'] > 1;

    // No need to create user account 1 if it already exists.
    try {
      $autocreate_user_1 = $autocreate_user_1 && !User::load(1);
    }
    catch (DatabaseExceptionWrapper $e) {
      // Missing schema, it will be created later on.
    }

    // Save the user entity object and created its schema if needed.
    try {
      if ($autocreate_user_1) {
        $permissions = [];
        $admin = FALSE;
        $values = [];
      }
      $user = $this->createUser($permissions, NULL, $admin, $values);
    }
    catch (EntityStorageException $e) {
      if ($this instanceof KernelTestBase) {
        $this->installEntitySchema('user');
        $user = $this->createUser($permissions, NULL, $admin, $values);
      }
      else {
        throw $e;
      }
    }

    // Ensure the anonymous user account exists.
    if (!User::load(0)) {
      $values = [
        'uid' => 0,
        'status' => 0,
        'name' => '',
      ];
      User::create($values)->save();
    }

    // If we automatically created user account 1, we need to create a regular
    // user account before setting up the current user service to avoid
    // potential false positives caused by access control bypass.
    if ($autocreate_user_1) {
      $user = $this->createUser($original_permissions, NULL, $original_admin, $original_values);
    }

    $this->setCurrentUser($user);

    return $user;
  }

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
   * @param array $values
   *   (optional) An array of initial user field values.
   *
   * @return \Drupal\user\Entity\User|false
   *   A fully loaded user object with pass_raw property, or FALSE if account
   *   creation fails.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the user creation fails.
   */
  protected function createUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) {
    // Create a role with the given permission set, if any.
    $rid = FALSE;
    if ($permissions) {
      $rid = $this->createRole($permissions);
      if (!$rid) {
        return FALSE;
      }
    }

    // Create a user assigned to that role.
    $edit = $values;
    if ($name) {
      $edit['name'] = $name;
    }
    elseif (!isset($values['name'])) {
      $edit['name'] = $this->randomMachineName();
    }
    $edit += [
      'mail' => $edit['name'] . '@example.com',
      'pass' => \Drupal::service('password_generator')->generate(),
      'status' => 1,
    ];
    if ($rid) {
      $edit['roles'] = [$rid];
    }

    if ($admin) {
      $edit['roles'][] = $this->createAdminRole();
    }

    $account = User::create($edit);
    $account->save();

    $valid_user = $account->id() !== NULL;
    $this->assertTrue($valid_user, new FormattableMarkup('User created with name %name and pass %pass', ['%name' => $edit['name'], '%pass' => $edit['pass']]), 'User login');
    if (!$valid_user) {
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
   *   (optional) The weight for the role. Defaults to NULL which sets the
   *   weight to maximum + 1.
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
   *   (optional) The weight for the role. Defaults to NULL which sets the
   *   weight to maximum + 1.
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

    $this->assertIdentical($result, SAVED_NEW, new FormattableMarkup('Created role ID @rid with name @name.', [
      '@name' => var_export($role->label(), TRUE),
      '@rid' => var_export($role->id(), TRUE),
    ]), 'Role');

    if ($result === SAVED_NEW) {
      // Grant the specified permissions to the role, if any.
      if (!empty($permissions)) {
        $this->grantPermissions($role, $permissions);
        $assigned_permissions = Role::load($role->id())->getPermissions();
        $missing_permissions = array_diff($permissions, $assigned_permissions);
        $this->assertEmpty($missing_permissions);
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
        $this->fail(new FormattableMarkup('Invalid permission %permission.', ['%permission' => $permission]), 'Role');
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * Grant permissions to a user role.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The user role entity to alter.
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
