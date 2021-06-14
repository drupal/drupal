<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy user functionality.
 *
 * @group user
 * @group legacy
 */
class UserLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  public function testUserPassword() {
    $this->expectDeprecation('user_password() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Password\PasswordGeneratorInterface::generate() instead. See https://www.drupal.org/node/3153113');
    $this->assertNotEmpty(user_password());
  }

}
