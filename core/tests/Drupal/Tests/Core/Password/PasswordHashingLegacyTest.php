<?php

namespace Drupal\Tests\Core\Password;

use Drupal\Core\Password\PhpassHashedPassword;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for deprecated password hashing API.
 *
 * @group System
 * @group legacy
 */
class PasswordHashingLegacyTest extends UnitTestCase {

  /**
   * @covers \Drupal\Core\Password\PhpassHashedPassword
   */
  public function testDeprecation() {
    $this->expectDeprecation('\Drupal\Core\Password\PhpassHashedPassword is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. The password compatibility service has been moved to the phpass module. Use \Drupal\phpass\Password\PhpassHashedPassword instead. See https://www.drupal.org/node/3322420');
    $this->expectDeprecation('Calling Drupal\Core\Password\PhpassHashedPasswordBase::__construct() with numeric $countLog2 as the first parameter is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use PhpassHashedPasswordInterface::__construct() with $corePassword parameter set to an instance of Drupal\Core\Password\PhpPassword instead. See https://www.drupal.org/node/3322420');
    $passwordService = new PhpassHashedPassword(4);
    $this->assertInstanceOf(PhpassHashedPassword::class, $passwordService);
  }

}
