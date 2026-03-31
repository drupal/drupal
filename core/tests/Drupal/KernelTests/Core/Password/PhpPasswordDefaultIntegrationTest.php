<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Password;

use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Password\PhpPassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Integration tests for the PHP password hashing service.
 */
#[Group('Password')]
#[RunTestsInSeparateProcesses]
#[CoversClass(PhpPassword::class)]
class PhpPasswordDefaultIntegrationTest extends PasswordTestBase {

  /**
   * Tests that the default password hashing algorithm is used.
   */
  public function testDefaultHashing(): void {
    $password = 'correct horse battery staple';
    $hash = $this->container->get(PasswordInterface::class)->hash($password);
    $this->assertStringStartsWith(implode(['$', PASSWORD_BCRYPT, '$']), $hash);
  }

}
