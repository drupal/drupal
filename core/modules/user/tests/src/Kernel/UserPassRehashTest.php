<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests user_pass_rehash().
 *
 * @group user
 */
class UserPassRehashTest extends KernelTestBase {

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

    $hash_a = user_pass_rehash($user_a, $timestamp);
    $hash_b = user_pass_rehash($user_b, $timestamp);

    $this->assertNotEquals($hash_a, $hash_b);
  }

}
