<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Password;

use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Password\PhpPassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Argon2id specific integration tests for the PHP password hashing service.
 */
#[Group('Password')]
#[RunTestsInSeparateProcesses]
#[CoversClass(PhpPassword::class)]
class PhpPasswordArgon2idIntegrationTest extends PasswordTestBase {

  /**
   * {@inheritdoc}
   */
  protected ?string $passwordAlgorithm = PASSWORD_ARGON2ID;

  /**
   * {@inheritdoc}
   */
  protected ?array $passwordOptions = [
    'memory_cost' => 1024,
    'time_cost' => 2,
    'threads' => 1,
  ];

  /**
   * Tests that the argon2id password hashing algorithm is used.
   */
  public function testArgon2idHashing(): void {
    $password = 'correct horse battery staple';
    $hash = $this->container->get(PasswordInterface::class)->hash($password);
    $this->assertStringStartsWith(implode(['$', PASSWORD_ARGON2ID, '$v=19$m=1024,t=2,p=1$']), $hash);
  }

}
