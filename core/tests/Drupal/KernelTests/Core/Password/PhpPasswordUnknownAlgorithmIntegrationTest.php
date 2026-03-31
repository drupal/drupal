<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Password;

// cspell:ignore vogon

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
class PhpPasswordUnknownAlgorithmIntegrationTest extends PasswordTestBase {

  /**
   * {@inheritdoc}
   */
  protected ?string $passwordAlgorithm = 'vogon poetry';

  /**
   * {@inheritdoc}
   */
  protected ?array $passwordOptions = [
    'bureaucracy' => 'max',
    'temper' => 'very bad',
  ];

  /**
   * Tests that the default password hashing algorithm is used.
   */
  public function testUnknownAlgorithmHashing(): void {
    $password = 'correct horse battery staple';
    $hash = $this->container->get(PasswordInterface::class)->hash($password);
    $defaultOptions = sprintf("%02d", PASSWORD_BCRYPT_DEFAULT_COST);
    $this->assertStringStartsWith(implode(['$', PASSWORD_DEFAULT, '$', $defaultOptions]), $hash);
  }

}
