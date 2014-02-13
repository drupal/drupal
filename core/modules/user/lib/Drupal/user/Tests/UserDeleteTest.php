<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserDeleteTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests user_delete() and user_delete_multiple() behavior.
 */
class UserDeleteTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User delete test',
      'description' => 'Test account deleting of users.',
      'group' => 'User',
    );
  }

  /**
   * Test deleting multiple users.
   */
  function testUserDeleteMultiple() {
    // Create a few users with permissions, so roles will be created.
    $user_a = $this->drupalCreateUser(array('access user profiles'));
    $user_b = $this->drupalCreateUser(array('access user profiles'));
    $user_c = $this->drupalCreateUser(array('access user profiles'));

    $uids = array($user_a->id(), $user_b->id(), $user_c->id());

    // These users should have a role
    $query = db_select('users_roles', 'r');
    $roles_created = $query
      ->fields('r', array('uid'))
      ->condition('uid', $uids)
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertTrue($roles_created > 0, 'Role assigments created for new users and deletion of role assigments can be tested');
    // We should be able to load one of the users.
    $this->assertTrue(user_load($user_a->id()), 'User is created and deltion of user can be tested');
    // Delete the users.
    user_delete_multiple($uids);
    // Test if the roles assignments are deleted.
    $query = db_select('users_roles', 'r');
    $roles_after_deletion = $query
      ->fields('r', array('uid'))
      ->condition('uid', $uids)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertTrue($roles_after_deletion == 0, 'Role assigments deleted along with users');
    // Test if the users are deleted, user_load() will return FALSE.
    $this->assertFalse(user_load($user_a->id()), format_string('User with id @uid deleted.', array('@uid' => $user_a->id())));
    $this->assertFalse(user_load($user_b->id()), format_string('User with id @uid deleted.', array('@uid' => $user_b->id())));
    $this->assertFalse(user_load($user_c->id()), format_string('User with id @uid deleted.', array('@uid' => $user_c->id())));
  }
}
