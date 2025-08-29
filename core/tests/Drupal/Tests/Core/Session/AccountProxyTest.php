<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests Drupal\Core\Session\AccountProxy.
 */
#[CoversClass(AccountProxy::class)]
#[Group('Session')]
class AccountProxyTest extends UnitTestCase {

  /**
   * Tests id.
   *
   * @legacy-covers ::id
   * @legacy-covers ::setInitialAccountId
   */
  public function testId(): void {
    $dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $dispatcher->dispatch(Argument::any(), Argument::any())->willReturn(new Event());
    $account_proxy = new AccountProxy($dispatcher->reveal());
    $this->assertSame(0, $account_proxy->id());
    $account_proxy->setInitialAccountId(1);
    $this->assertFalse(\Drupal::hasContainer());
    // If the following call loaded the user entity it would call
    // AccountProxy::loadUserEntity() which would fail because the container
    // does not exist.
    $this->assertSame(1, $account_proxy->id());
    $current_user = $this->prophesize(AccountInterface::class);
    $current_user->id()->willReturn(2);
    $account_proxy->setAccount($current_user->reveal());
    $this->assertSame(2, $account_proxy->id());
  }

  /**
   * Tests set initial account id exception.
   *
   * @legacy-covers ::setInitialAccountId
   */
  public function testSetInitialAccountIdException(): void {
    $this->expectException(\LogicException::class);
    $dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $dispatcher->dispatch(Argument::any(), Argument::any())->willReturn(new Event());
    $account_proxy = new AccountProxy($dispatcher->reveal());
    $current_user = $this->prophesize(AccountInterface::class);
    $account_proxy->setAccount($current_user->reveal());
    $account_proxy->setInitialAccountId(1);
  }

  /**
   * Tests has role.
   *
   * @legacy-covers ::hasRole
   */
  public function testHasRole(): void {
    $dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $dispatcher->dispatch(Argument::any(), Argument::any())->willReturn(new Event());
    $account_proxy = new AccountProxy($dispatcher->reveal());
    $this->assertTrue($account_proxy->hasRole(RoleInterface::ANONYMOUS_ID));

    $current_user = $this->prophesize(UserSession::class);
    $current_user->id()->willReturn(2);
    $current_user->hasRole(RoleInterface::AUTHENTICATED_ID)->willReturn(TRUE);
    $account_proxy->setAccount($current_user->reveal());
    $this->assertTrue($account_proxy->hasRole(RoleInterface::AUTHENTICATED_ID));
  }

}
