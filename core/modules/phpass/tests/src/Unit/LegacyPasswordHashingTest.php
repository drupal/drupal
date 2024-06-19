<?php

declare(strict_types=1);

namespace Drupal\Tests\phpass\Unit;

use Drupal\phpass\Password\PhpassHashedPassword;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for password hashing API.
 *
 * Legacy tests, deprecated in drupal:10.1.0 and removed from drupal:11.0.0 as
 * soon as PhpassHashedPassword::__construct() with $corePassword parameter is
 * enforced to be an instance of Drupal\Core\Password\PhpPassword.
 *
 * @see https://www.drupal.org/node/3322420
 *
 * @coversDefaultClass \Drupal\phpass\Password\PhpassHashedPassword
 * @group phpass
 * @group legacy
 */
class LegacyPasswordHashingTest extends UnitTestCase {

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
    $this->expectDeprecation('Calling Drupal\Core\Password\PhpassHashedPasswordBase::__construct() with numeric $countLog2 as the first parameter is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use PhpassHashedPasswordInterface::__construct() with $corePassword parameter set to an instance of Drupal\Core\Password\PhpPassword instead. See https://www.drupal.org/node/3322420');
    $this->password = $this->randomMachineName();
    $this->passwordHasher = new PhpassHashedPassword(1);
    $this->hashedPassword = $this->passwordHasher->hash($this->password);
    $this->md5HashedPassword = 'U' . $this->passwordHasher->hash(md5($this->password));
  }

  /**
   * Tests a password needs update.
   *
   * @covers ::needsRehash
   */
  public function testPasswordNeedsUpdate(): void {
    // The md5 password should be flagged as needing an update.
    $this->assertTrue($this->passwordHasher->needsRehash($this->md5HashedPassword), 'Upgraded md5 password hash needs a new hash.');
  }

  /**
   * Tests password hashing.
   *
   * @covers ::hash
   * @covers ::getCountLog2
   * @covers ::base64Encode
   * @covers ::check
   * @covers ::generateSalt
   * @covers ::needsRehash
   */
  public function testPasswordHashing(): void {
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
   * @covers ::__construct
   * @covers ::hash
   * @covers ::getCountLog2
   * @covers ::check
   * @covers ::needsRehash
   */
  public function testPasswordRehashing(): void {
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
   * Tests password validation when the hash is NULL.
   *
   * @covers ::check
   */
  public function testEmptyHash(): void {
    $this->assertFalse($this->passwordHasher->check($this->password, NULL));
    $this->assertFalse($this->passwordHasher->check($this->password, ''));
  }

}
