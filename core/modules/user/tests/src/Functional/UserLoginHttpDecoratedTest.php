<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\user_auth_decorator_test\UserAuthDecorator;

/**
 * Tests login and password reset via direct HTTP with a user.auth decorator.
 *
 * The decorator does not implement UserAuthenticationInterface.
 *
 * @group user
 */
class UserLoginHttpDecoratedTest extends UserLoginHttpTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user_auth_decorator_test'];

  /**
   * Test that the UserAuthDecorator is providing user.auth.
   */
  public function testServiceDecorated(): void {
    $service = \Drupal::service('user.auth');
    $this->assertInstanceOf(UserAuthDecorator::class, $service);
  }

}
