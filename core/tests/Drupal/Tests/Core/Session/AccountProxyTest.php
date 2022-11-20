<?php

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use Prophecy\Argument;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\Core\Session\AccountProxy
 * @group Session
 */
class AccountProxyTest extends UnitTestCase {

  /**
   * @covers ::id
   * @covers ::setInitialAccountId
   */
  public function testId() {
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
   * @covers ::setInitialAccountId
   */
  public function testSetInitialAccountIdException() {
    $this->expectException(\LogicException::class);
    $dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $dispatcher->dispatch(Argument::any(), Argument::any())->willReturn(new Event());
    $account_proxy = new AccountProxy($dispatcher->reveal());
    $current_user = $this->prophesize(AccountInterface::class);
    $account_proxy->setAccount($current_user->reveal());
    $account_proxy->setInitialAccountId(1);
  }

  /**
   * @covers ::hasRole
   */
  public function testHasRole() {
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
