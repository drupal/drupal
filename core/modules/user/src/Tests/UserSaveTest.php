<?php

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Tests account saving for arbitrary new uid.
 *
 * @group user
 */
class UserSaveTest extends WebTestBase {

  /**
   * Test creating a user with arbitrary uid.
   */
  function testUserImport() {
    // User ID must be a number that is not in the database.

    $uids = \Drupal::entityManager()->getStorage('user')->getQuery()
      ->sort('uid', 'DESC')
      ->range(0, 1)
      ->execute();
    $max_uid = reset($uids);
    $test_uid = $max_uid + mt_rand(1000, 1000000);
    $test_name = $this->randomMachineName();

    // Create the base user, based on drupalCreateUser().
    $user = User::create([
      'name' => $test_name,
      'uid' => $test_uid,
      'mail' => $test_name . '@example.com',
      'pass' => user_password(),
      'status' => 1,
    ]);
    $user->enforceIsNew();
    $user->save();

    // Test if created user exists.
    $user_by_uid = User::load($test_uid);
    $this->assertTrue($user_by_uid, 'Loading user by uid.');

    $user_by_name = user_load_by_name($test_name);
    $this->assertTrue($user_by_name, 'Loading user by name.');
  }

  /**
   * Ensures that an existing password is unset after the user was saved.
   */
  function testExistingPasswordRemoval() {
    /** @var \Drupal\user\Entity\User $user */
    $user = User::create(['name' => $this->randomMachineName()]);
    $user->save();
    $user->setExistingPassword('existing password');
    $this->assertNotNull($user->pass->existing);
    $user->save();
    $this->assertNull($user->pass->existing);
  }

}
