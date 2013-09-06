<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\PasswordHashingTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Password\PhpassHashedPassword;

/**
 * Unit tests for password hashing API.
 */
class PasswordHashingTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'user');

  public static function getInfo() {
    return array(
      'name' => 'Password hashing',
      'description' => 'Password hashing unit tests.',
      'group' => 'System',
    );
  }

  /**
   * Test password hashing.
   */
  function testPasswordHashing() {
    // Set a log2 iteration count that is deliberately out of bounds to test
    // that it is corrected to be within bounds.
    $password_hasher = new PhpassHashedPassword(1);
    // Set up a fake $account with a password 'baz', hashed with md5.
    $password = 'baz';
    $account = entity_create('user', array('name' => 'foo', 'pass' => md5($password)));
    // The md5 password should be flagged as needing an update.
    $this->assertTrue($password_hasher->userNeedsNewHash($account), 'User with md5 password needs a new hash.');
    // Re-hash the password.
    $old_hash = $account->getPassword();
    $account->setPassword($password_hasher->hash($password));
    $this->assertIdentical($password_hasher->getCountLog2($account->getPassword()), $password_hasher::MIN_HASH_COUNT, 'Re-hashed password has the minimum number of log2 iterations.');
    $this->assertTrue($account->getPassword() != $old_hash, 'Password hash changed.');
    $this->assertTrue($password_hasher->check($password, $account), 'Password check succeeds.');
    // Since the log2 setting hasn't changed and the user has a valid password,
    // $password_hasher->userNeedsNewHash() should return FALSE.
    $this->assertFalse($password_hasher->userNeedsNewHash($account), 'User does not need a new hash.');

    // Increment the log2 iteration to MIN + 1.
    $password_hasher = new PhpassHashedPassword($password_hasher::MIN_HASH_COUNT + 1);
    $this->assertTrue($password_hasher->userNeedsNewHash($account), 'User needs a new hash after incrementing the log2 count.');
    // Re-hash the password.
    $old_hash = $account->getPassword();
    $account->setPassword($password_hasher->hash($password));
    $this->assertIdentical($password_hasher->getCountLog2($account->getPassword()), $password_hasher::MIN_HASH_COUNT + 1, 'Re-hashed password has the correct number of log2 iterations.');
    $this->assertTrue($account->getPassword() != $old_hash, 'Password hash changed again.');
    // Now the hash should be OK.
    $this->assertFalse($password_hasher->userNeedsNewHash($account), 'Re-hashed password does not need a new hash.');
    $this->assertTrue($password_hasher->check($password, $account), 'Password check succeeds with re-hashed password.');
  }
}
