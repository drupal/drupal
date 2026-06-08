<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\OneTimeAuthentication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests OneTimeAuthentication service.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
#[CoversClass(OneTimeAuthentication::class)]
class OneTimeAuthenticationTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
  ];

  /**
   * Tests uniqueness of hashes when no password is set.
   */
  public function testUniqueHashNoPasswordValue(): void {
    $this->installEntitySchema('user');

    $timestamp = \Drupal::time()->getRequestTime();

    $user_a = $this->createUser(
      [],
      NULL,
      FALSE,
      [
        'uid' => 12,
        'mail' => '3user@example.com',
        'login' => $timestamp - 1000,
      ]
    );
    $user_b = $this->createUser(
      [],
      NULL,
      FALSE,
      [
        'uid' => 123,
        'mail' => 'user@example.com',
        'login' => $timestamp - 1000,
      ]
    );

    // Unset passwords after the users are created in order to avoid
    // (different) password hashes being generated for the empty strings.
    $user_a->setPassword('');
    $user_b->setPassword('');

    $oneTimeAuthentication = \Drupal::service(OneTimeAuthentication::class);
    $hash_a = $oneTimeAuthentication->generateHmac($user_a, $timestamp);
    $hash_b = $oneTimeAuthentication->generateHmac($user_b, $timestamp);

    $this->assertNotEquals($hash_a, $hash_b);
  }

}
