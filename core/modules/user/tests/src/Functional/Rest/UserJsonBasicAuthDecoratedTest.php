<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional\Rest;

use Drupal\user_auth_decorator_test\UserAuthDecorator;

/**
 * Run UserJsonBasicAuthTest with a user.auth decorator.
 *
 * @group rest
 * @group #slow
 */
class UserJsonBasicAuthDecoratedTest extends UserJsonBasicAuthTest {
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
