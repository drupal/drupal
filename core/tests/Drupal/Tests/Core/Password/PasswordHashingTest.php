<?php

/**
 * @file
 * Contains Drupal\system\Tests\System\PasswordHashingTest.
 */

namespace Drupal\Tests\Core\Password;

use Drupal\Core\Password\PhpassHashedPassword;
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
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\user\UserInterface
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
  protected $md5Password;

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
  protected function setUp() {
    parent::setUp();
    $this->user = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();
    $this->passwordHasher = new PhpassHashedPassword(1);
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
   * Test a password needs update.
   *
   * @covers ::userNeedsNewHash
   */
  public function testPasswordNeedsUpdate() {
    $this->user->expects($this->any())
      ->method('getPassword')
      ->will($this->returnValue($this->md5Password));
    // The md5 password should be flagged as needing an update.
    $this->assertTrue($this->passwordHasher->userNeedsNewHash($this->user), 'User with md5 password needs a new hash.');
  }

  /**
   * Test password hashing.
   *
   * @covers ::hash
   * @covers ::getCountLog2
   * @covers ::check
   * @covers ::userNeedsNewHash
   */
  public function testPasswordHashing() {
    $this->hashedPassword = $this->passwordHasher->hash($this->password);
    $this->user->expects($this->any())
      ->method('getPassword')
      ->will($this->returnValue($this->hashedPassword));
    $this->assertSame($this->passwordHasher->getCountLog2($this->hashedPassword), PhpassHashedPassword::MIN_HASH_COUNT, 'Hashed password has the minimum number of log2 iterations.');
    $this->assertNotEquals($this->hashedPassword, $this->md5Password, 'Password hash changed.');
    $this->assertTrue($this->passwordHasher->check($this->password, $this->user), 'Password check succeeds.');
    // Since the log2 setting hasn't changed and the user has a valid password,
    // userNeedsNewHash() should return FALSE.
    $this->assertFalse($this->passwordHasher->userNeedsNewHash($this->user), 'User does not need a new hash.');
  }

  /**
   * Tests password rehashing.
   *
   * @covers ::hash
   * @covers ::getCountLog2
   * @covers ::check
   * @covers ::userNeedsNewHash
   */
  public function testPasswordRehashing() {

    // Increment the log2 iteration to MIN + 1.
    $this->passwordHasher = new PhpassHashedPassword(PhpassHashedPassword::MIN_HASH_COUNT + 1);
    $this->assertTrue($this->passwordHasher->userNeedsNewHash($this->user), 'User needs a new hash after incrementing the log2 count.');
    // Re-hash the password.
    $rehashed_password = $this->passwordHasher->hash($this->password);

    $this->user->expects($this->any())
      ->method('getPassword')
      ->will($this->returnValue($rehashed_password));
    $this->assertSame($this->passwordHasher->getCountLog2($rehashed_password), PhpassHashedPassword::MIN_HASH_COUNT + 1, 'Re-hashed password has the correct number of log2 iterations.');
    $this->assertNotEquals($rehashed_password, $this->hashedPassword, 'Password hash changed again.');

    // Now the hash should be OK.
    $this->assertFalse($this->passwordHasher->userNeedsNewHash($this->user), 'Re-hashed password does not need a new hash.');
    $this->assertTrue($this->passwordHasher->check($this->password, $this->user), 'Password check succeeds with re-hashed password.');
  }

}

/**
 * A fake class for tests.
 */
class FakePhpassHashedPassword extends PhpassHashedPassword {

  function __construct() {
    // Noop.
  }

  // Expose this method as public for tests.
  public function enforceLog2Boundaries($count_log2) {
    return parent::enforceLog2Boundaries($count_log2);
  }

}
