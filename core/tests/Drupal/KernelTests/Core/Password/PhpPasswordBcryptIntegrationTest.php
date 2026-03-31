<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Password;

use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Password\PhpPassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Bcrypt specific integration tests for the PHP password hashing service.
 */
#[Group('Password')]
#[RunTestsInSeparateProcesses]
#[CoversClass(PhpPassword::class)]
class PhpPasswordBcryptIntegrationTest extends PasswordTestBase {

  /**
   * {@inheritdoc}
   */
  protected ?string $passwordAlgorithm = PASSWORD_BCRYPT;

  /**
   * {@inheritdoc}
   */
  protected ?array $passwordOptions = ['cost' => 5];

  /**
   * Tests that the bcrypt password hashing algorithm is used.
   */
  public function testBcryptHashing(): void {
    $password = 'correct horse battery staple';
    $hash = $this->container->get(PasswordInterface::class)->hash($password);
    $this->assertStringStartsWith(implode(['$', PASSWORD_BCRYPT, '$05$']), $hash);
  }

}
