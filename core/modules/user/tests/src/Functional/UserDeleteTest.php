<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests account deleting of users.
 *
 * @group user
 */
class UserDeleteTest extends BrowserTestBase {

  /**
   * Test deleting multiple users.
   */
  public function testUserDeleteMultiple() {
    // Create a few users with permissions, so roles will be created.
    $user_a = $this->drupalCreateUser(['access user profiles']);
    $user_b = $this->drupalCreateUser(['access user profiles']);
    $user_c = $this->drupalCreateUser(['access user profiles']);

    $uids = [$user_a->id(), $user_b->id(), $user_c->id()];

    // These users should have a role
    $query = db_select('user__roles', 'r');
    $roles_created = $query
      ->fields('r', ['entity_id'])
      ->condition('entity_id', $uids, 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertTrue($roles_created > 0, 'Role assignments created for new users and deletion of role assignments can be tested');
    // We should be able to load one of the users.
    $this->assertTrue(User::load($user_a->id()), 'User is created and deletion of user can be tested');
    // Delete the users.
    user_delete_multiple($uids);
    // Test if the roles assignments are deleted.
    $query = db_select('user__roles', 'r');
    $roles_after_deletion = $query
      ->fields('r', ['entity_id'])
      ->condition('entity_id', $uids, 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertTrue($roles_after_deletion == 0, 'Role assignments deleted along with users');
    // Test if the users are deleted, User::load() will return NULL.
    $this->assertNull(User::load($user_a->id()), format_string('User with id @uid deleted.', ['@uid' => $user_a->id()]));
    $this->assertNull(User::load($user_b->id()), format_string('User with id @uid deleted.', ['@uid' => $user_b->id()]));
    $this->assertNull(User::load($user_c->id()), format_string('User with id @uid deleted.', ['@uid' => $user_c->id()]));
  }

}
