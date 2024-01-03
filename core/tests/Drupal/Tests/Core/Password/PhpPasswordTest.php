<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Password;

use Drupal\Core\Password\PhpPassword;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for password hashing API.
 *
 * @coversDefaultClass \Drupal\Core\Password\PhpPassword
 * @group System
 */
class PhpPasswordTest extends UnitTestCase {

  /**
   * The raw password.
   */
  protected string $password;

  /**
   * The hashed password.
   */
  protected string $passwordHash;

  /**
   * The password hasher under test.
   */
  protected PasswordInterface $passwordHasher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->password = $this->randomMachineName();
    $this->passwordHasher = new PhpPassword(PASSWORD_BCRYPT, ['cost' => 5]);
    $this->passwordHash = $this->passwordHasher->hash($this->password);
  }

  /**
   * Tests a password needs update.
   *
   * @covers ::hash
   * @covers ::needsRehash
   */
  public function testPasswordNeedsUpdate() {
    $weakHash = (new PhpPassword(PASSWORD_BCRYPT, ['cost' => 4]))->hash($this->password);
    $this->assertTrue($this->passwordHasher->needsRehash($weakHash), 'Password hash with weak cost settings needs a new hash.');
  }

  /**
   * Tests password hashing.
   *
   * @covers ::check
   * @covers ::needsRehash
   */
  public function testPasswordChecking() {
    $this->assertTrue($this->passwordHasher->check($this->password, $this->passwordHash), 'Password check succeeds.');
    $this->assertFalse($this->passwordHasher->needsRehash($this->passwordHash), 'Does not need a new hash.');
  }

  /**
   * Tests password rehashing.
   *
   * @covers ::hash
   * @covers ::check
   * @covers ::needsRehash
   */
  public function testPasswordRehashing() {
    // Increment the cost by one.
    $strongHasher = new PhpPassword(PASSWORD_BCRYPT, ['cost' => 6]);
    $this->assertTrue($strongHasher->needsRehash($this->passwordHash), 'Needs a new hash after incrementing the cost option.');
    // Re-hash the password.
    $rehashedPassword = $strongHasher->hash($this->password);
    $this->assertNotEquals($rehashedPassword, $this->passwordHash, 'Password hash changed again.');

    // Now the hash should be OK.
    $this->assertFalse($strongHasher->needsRehash($rehashedPassword), 'Re-hashed password does not need a new hash.');
    $this->assertTrue($strongHasher->check($this->password, $rehashedPassword), 'Password check succeeds with re-hashed password.');
    $this->assertTrue($this->passwordHasher->check($this->password, $rehashedPassword), 'Password check succeeds with re-hashed password with original hasher.');
  }

  /**
   * Verifies that passwords longer than 512 bytes are not hashed.
   *
   * @covers ::hash
   *
   * @dataProvider providerLongPasswords
   */
  public function testLongPassword($password, $allowed) {

    $passwordHash = $this->passwordHasher->hash($password);

    if ($allowed) {
      $this->assertNotFalse($passwordHash);
    }
    else {
      $this->assertFalse($passwordHash);
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
    $len = (int) floor(PasswordInterface::PASSWORD_MAX_LENGTH / 3);
    $diff = PasswordInterface::PASSWORD_MAX_LENGTH % 3;
    $passwords['utf8'] = [str_repeat('€', $len), TRUE];
    // 512 byte long password is allowed.
    $passwords['ut8_extended'] = [$passwords['utf8'][0] . str_repeat('x', $diff), TRUE];

    // Check a string of 3-byte UTF-8 characters, 513 byte long password is
    // allowed.
    $passwords['utf8_too_long'] = [str_repeat('€', $len + 1), FALSE];
    return $passwords;
  }

  /**
   * Tests password check in case provided hash is NULL.
   *
   * @covers ::check
   */
  public function testEmptyHash(): void {
    $this->assertFalse($this->passwordHasher->check($this->password, NULL));
    $this->assertFalse($this->passwordHasher->check($this->password, ''));
  }

}
