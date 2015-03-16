<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\UserUnitTestBase.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Provides a common test base for user views tests.
 */
abstract class UserUnitTestBase extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user_test_views', 'user', 'system', 'field');

  /**
   * Users to use during this test.
   *
   * @var array
   */
  protected $users = array();

  /**
   * The entity storage for roles.
   *
   * @var \Drupal\user\RoleStorage
   */
  protected $roleStorage;

  /**
   * The entity storage for users.
   *
   * @var \Drupal\user\UserStorage
   */
  protected $userStorage;

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('user_test_views'));

    $this->installEntitySchema('user');

    $entity_manager = $this->container->get('entity.manager');
    $this->roleStorage = $entity_manager->getStorage('user_role');
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * Set some test data for permission related tests.
   */
  protected function setupPermissionTestData() {
    // Setup a role without any permission.
    $this->roleStorage->create(array('id' => 'authenticated'))
      ->save();
    $this->roleStorage->create(array('id' => 'no_permission'))
      ->save();
    // Setup a role with just one permission.
    $this->roleStorage->create(array('id' => 'one_permission'))
      ->save();
    user_role_grant_permissions('one_permission', array('administer permissions'));
    // Setup a role with multiple permissions.
    $this->roleStorage->create(array('id' => 'multiple_permissions'))
      ->save();
    user_role_grant_permissions('multiple_permissions', array('administer permissions', 'administer users', 'access user profiles'));

    // Setup a user without an extra role.
    $this->users[] = $account = $this->userStorage->create(array());
    $account->save();
    // Setup a user with just the first role (so no permission beside the
    // ones from the authenticated role).
    $this->users[] = $account = $this->userStorage->create(array('name' => 'first_role'));
    $account->addRole('no_permission');
    $account->save();
    // Setup a user with just the second role (so one additional permission).
    $this->users[] = $account = $this->userStorage->create(array('name' => 'second_role'));
    $account->addRole('one_permission');
    $account->save();
    // Setup a user with both the second and the third role.
    $this->users[] = $account = $this->userStorage->create(array('name' => 'second_third_role'));
    $account->addRole('one_permission');
    $account->addRole('multiple_permissions');
    $account->save();
  }

}
