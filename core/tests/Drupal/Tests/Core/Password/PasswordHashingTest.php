<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Password\PasswordHashingTest.
 */

namespace Drupal\Tests\Core\Password;

use Drupal\Core\Password\PhpassHashedPassword;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for password hashing API.
 *
 * @coversDefaultClass \Drupal\Core\Password\PhpassHashedPassword
 * @group System
 */
class PasswordHashingTest extends UnitTestCase {

  /**
   * The user for testing.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The raw password.
   *
   * @var string
   */
  protected $password;

  /**
   * The md5 password.
   *
   * @var string
   */
  protected $md5HashedPassword;

  /**
   * The hashed password.
   *
   * @var string
   */
  protected $hashedPassword;

  /**
   * The password hasher under test.
   *
   * @var \Drupal\Core\Password\PhpassHashedPassword
   */
  protected $passwordHasher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->password = $this->randomMachineName();
    $this->passwordHasher = new PhpassHashedPassword(1);
    $this->hashedPassword = $this->passwordHasher->hash($this->password);
    $this->md5HashedPassword = 'U' . $this->passwordHasher->hash(md5($this->password));
  }

  /**
   * Tests the hash count boundaries are enforced.
   *
   * @covers ::enforceLog2Boundaries
   */
  public function testWithinBounds() {
    $hasher = new FakePhpassHashedPassword();
    $this->assertEquals(PhpassHashedPassword::MIN_HASH_COUNT, $hasher->enforceLog2Boundaries(1), "Min hash count enforced");
    $this->assertEquals(PhpassHashedPassword::MAX_HASH_COUNT, $hasher->enforceLog2Boundaries(100), "Max hash count enforced");
  }

  /**
   * Tests a password needs update.
   *
   * @covers ::needsRehash
   */
  public function testPasswordNeedsUpdate() {
    // The md5 password should be flagged as needing an update.
    $this->assertTrue($this->passwordHasher->needsRehash($this->md5HashedPassword), 'Upgraded md5 password hash needs a new hash.');
  }

  /**
   * Tests password hashing.
   *
   * @covers ::hash
   * @covers ::getCountLog2
   * @covers ::check
   * @covers ::needsRehash
   */
  public function testPasswordHashing() {
    $this->assertSame(PhpassHashedPassword::MIN_HASH_COUNT, $this->passwordHasher->getCountLog2($this->hashedPassword), 'Hashed password has the minimum number of log2 iterations.');
    $this->assertNotEquals($this->hashedPassword, $this->md5HashedPassword, 'Password hashes not the same.');
    $this->assertTrue($this->passwordHasher->check($this->password, $this->md5HashedPassword), 'Password check succeeds.');
    $this->assertTrue($this->passwordHasher->check($this->password, $this->hashedPassword), 'Password check succeeds.');
    // Since the log2 setting hasn't changed and the user has a valid password,
    // userNeedsNewHash() should return FALSE.
    $this->assertFalse($this->passwordHasher->needsRehash($this->hashedPassword), 'Does not need a new hash.');
  }

  /**
   * Tests password rehashing.
   *
   * @covers ::hash
   * @covers ::getCountLog2
   * @covers ::check
   * @covers ::needsRehash
   */
  public function testPasswordRehashing() {
    // Increment the log2 iteration to MIN + 1.
    $password_hasher = new PhpassHashedPassword(PhpassHashedPassword::MIN_HASH_COUNT + 1);
    $this->assertTrue($password_hasher->needsRehash($this->hashedPassword), 'Needs a new hash after incrementing the log2 count.');
    // Re-hash the password.
    $rehashed_password = $password_hasher->hash($this->password);
    $this->assertSame(PhpassHashedPassword::MIN_HASH_COUNT + 1, $password_hasher->getCountLog2($rehashed_password), 'Re-hashed password has the correct number of log2 iterations.');
    $this->assertNotEquals($rehashed_password, $this->hashedPassword, 'Password hash changed again.');

    // Now the hash should be OK.
    $this->assertFalse($password_hasher->needsRehash($rehashed_password), 'Re-hashed password does not need a new hash.');
    $this->assertTrue($password_hasher->check($this->password, $rehashed_password), 'Password check succeeds with re-hashed password.');
    $this->assertTrue($this->passwordHasher->check($this->password, $rehashed_password), 'Password check succeeds with re-hashed password with original hasher.');
  }

  /**
   * Verifies that passwords longer than 512 bytes are not hashed.
   *
   * @covers ::crypt
   *
   * @dataProvider providerLongPasswords
   */
  public function testLongPassword($password, $allowed) {

    $hashed_password = $this->passwordHasher->hash($password);

    if ($allowed) {
      $this->assertNotFalse($hashed_password);
    }
    else {
      $this->assertFalse($hashed_password);
    }
  }

  /**
   * Provides the test matrix for testLongPassword().
   */
  public function providerLongPasswords() {
    // '512 byte long password is allowed.'
    $passwords['allowed'] = [str_repeat('x', PasswordInterface::PASSWORD_MAX_LENGTH), TRUE];
    // 513 byte long password is not allowed.
    $passwords['too_long'] = [str_repeat('x', PasswordInterface::PASSWORD_MAX_LENGTH + 1), FALSE];

    // Check a string of 3-byte UTF-8 characters, 510 byte long password is
    // allowed.
    $len = floor(PasswordInterface::PASSWORD_MAX_LENGTH / 3);
    $diff = PasswordInterface::PASSWORD_MAX_LENGTH % 3;
    $passwords['utf8'] = [str_repeat('€', $len), TRUE];
    // 512 byte long password is allowed.
    $passwords['ut8_extended'] = [$passwords['utf8'][0] . str_repeat('x', $diff), TRUE];

    // Check a string of 3-byte UTF-8 characters, 513 byte long password is
    // allowed.
    $passwords['utf8_too_long'] = [str_repeat('€', $len + 1), FALSE];
    return $passwords;
  }

}

/**
 * A fake class for tests.
 */
class FakePhpassHashedPassword extends PhpassHashedPassword {

  public function __construct() {
    // Noop.
  }

  /**
   * Exposes this method as public for tests.
   */
  public function enforceLog2Boundaries($count_log2) {
    return parent::enforceLog2Boundaries($count_log2);
  }

}
