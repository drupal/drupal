<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserSaveTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

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
    $max_uid = db_query('SELECT MAX(uid) FROM {users}')->fetchField();
    $test_uid = $max_uid + mt_rand(1000, 1000000);
    $test_name = $this->randomMachineName();

    // Create the base user, based on drupalCreateUser().
    $user = entity_create('user', array(
      'name' => $test_name,
      'uid' => $test_uid,
      'mail' => $test_name . '@example.com',
      'pass' => user_password(),
      'status' => 1,
    ));
    $user->enforceIsNew();
    $user->save();

    // Test if created user exists.
    $user_by_uid = user_load($test_uid);
    $this->assertTrue($user_by_uid, 'Loading user by uid.');

    $user_by_name = user_load_by_name($test_name);
    $this->assertTrue($user_by_name, 'Loading user by name.');
  }
}
