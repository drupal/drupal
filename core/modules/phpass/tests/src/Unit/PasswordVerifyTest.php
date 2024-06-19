<?php

declare(strict_types=1);

namespace Drupal\Tests\phpass\Unit;

use Drupal\phpass\Password\PhpassHashedPassword;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Password\PhpPassword;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for password hashing API.
 *
 * @coversDefaultClass \Drupal\phpass\Password\PhpassHashedPassword
 * @group phpass
 */
class PasswordVerifyTest extends UnitTestCase {

  /**
   * Tests that hash() is forwarded to corePassword instance.
   *
   * @covers ::hash
   */
  public function testPasswordHash(): void {
    $samplePassword = $this->randomMachineName();
    $sampleHash = $this->randomMachineName();

    $corePassword = $this->prophesize(PasswordInterface::class);
    $corePassword->hash($samplePassword)->willReturn($sampleHash);

    $passwordService = new PhpassHashedPassword($corePassword->reveal());
    $result = $passwordService->hash($samplePassword);

    $this->assertSame($sampleHash, $result, 'Calls to hash() are forwarded to core password service.');
  }

  /**
   * Tests that needsRehash() is forwarded to corePassword instance.
   *
   * @covers ::needsRehash
   */
  public function testPasswordNeedsRehash(): void {
    $sampleHash = $this->randomMachineName();

    $corePassword = $this->prophesize(PasswordInterface::class);
    $corePassword->needsRehash($sampleHash)->willReturn(TRUE);

    $passwordService = new PhpassHashedPassword($corePassword->reveal());
    $result = $passwordService->needsRehash($sampleHash);
    $this->assertTrue($result, 'Calls to needsRehash() are forwarded to core password service.');
  }

  /**
   * Tests that check() is forwarded to corePassword instance if hash settings are not recognized.
   *
   * @covers ::check
   */
  public function testPasswordCheckUnknownHash(): void {
    $samplePassword = $this->randomMachineName();
    $sampleHash = $this->randomMachineName();

    $corePassword = $this->prophesize(PasswordInterface::class);
    $corePassword->check($samplePassword, $sampleHash)->willReturn(TRUE);

    $passwordService = new PhpassHashedPassword($corePassword->reveal());
    $result = $passwordService->check($samplePassword, $sampleHash);
    $this->assertTrue($result, 'Calls to check() are forwarded to core password service if hash settings are not recognized.');
  }

  /**
   * Tests that check() verifies passwords if hash settings are supported.
   *
   * @covers ::check
   * @covers ::crypt
   * @covers ::getCountLog2
   * @covers ::enforceLog2Boundaries
   * @covers ::base64Encode
   */
  public function testPasswordCheckSupported(): void {
    $validPassword = 'valid password';

    // cspell:disable
    $passwordHash = '$S$5TOxWPdvJRs0P/xZBdrrPlGgzViOS0drHu3jaIjitesfttrp18bk';
    $passwordLayered = 'U$S$5vNHDQyLqCTvsYBLWBUWXJWhA0m3DTpBh04acFEOGB.bKBclhKgo';
    // cspell:enable

    $invalidPassword = 'invalid password';

    $corePassword = $this->prophesize(PasswordInterface::class);
    $corePassword->check()->shouldNotBeCalled();

    $passwordService = new PhpassHashedPassword($corePassword->reveal());

    $result = $passwordService->check($validPassword, $passwordHash);
    $this->assertTrue($result, 'Accepts valid passwords created prior to 10.1.x');
    $result = $passwordService->check($invalidPassword, $passwordHash);
    $this->assertFalse($result, 'Rejects invalid passwords created prior to 10.1.x');

    $result = $passwordService->check($validPassword, $passwordLayered);
    $this->assertTrue($result, 'Accepts valid passwords migrated from sites running 6.x');
    $result = $passwordService->check($invalidPassword, $passwordLayered);
    $this->assertFalse($result, 'Rejects invalid passwords migrated from sites running 6.x');
  }

  /**
   * Tests the hash count boundaries are enforced.
   *
   * @covers ::enforceLog2Boundaries
   */
  public function testWithinBounds(): void {
    $hasher = new PhpassHashedPasswordLog2BoundariesDouble();
    $this->assertEquals(PhpassHashedPassword::MIN_HASH_COUNT, $hasher->enforceLog2Boundaries(1), "Min hash count enforced");
    $this->assertEquals(PhpassHashedPassword::MAX_HASH_COUNT, $hasher->enforceLog2Boundaries(100), "Max hash count enforced");
  }

  /**
   * Verifies that passwords longer than 512 bytes are not hashed.
   *
   * @covers ::crypt
   *
   * @dataProvider providerLongPasswords
   */
  public function testLongPassword($password, $allowed): void {
    // cspell:disable
    $bogusHash = '$S$5TOxWPdvJRs0P/xZBdrrPlGgzViOS0drHu3jaIjitesfttrp18bk';
    // cspell:enable

    $passwordService = new PhpassHashedPassword(new PhpPassword());

    if ($allowed) {
      $hash = $passwordService->hash($password);
      $this->assertNotFalse($hash);
      $result = $passwordService->check($password, $hash);
      $this->assertTrue($result);
    }
    else {
      $result = $passwordService->check($password, $bogusHash);
      $this->assertFalse($result);
    }
  }

  /**
   * Provides the test matrix for testLongPassword().
   */
  public static function providerLongPasswords() {
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

}

/**
 * Test double for test coverage of enforceLog2Boundaries().
 */
class PhpassHashedPasswordLog2BoundariesDouble extends PhpassHashedPassword {

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
