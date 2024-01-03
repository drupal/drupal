<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\UserRolesCacheContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\UserRolesCacheContext
 * @group Cache
 */
class UserRolesCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   */
  public function testCalculatedRole(): void {
    $current_user = $this->prophesize(AccountInterface::class);
    // Ensure the ID is not 1. This cache context gives user 1 a special superuser value.
    $current_user->id()->willReturn(2);
    $current_user->getRoles()->willReturn(['role1', 'role2']);
    $cache_context = new UserRolesCacheContext($current_user->reveal());
    $this->assertSame('true', $cache_context->getContext('role1'));
    $this->assertSame('false', $cache_context->getContext('role-not-held'));
  }

}
