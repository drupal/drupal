<?php

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Session\AccountProxy;

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
    $account_proxy = new AccountProxy();
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
    $this->setExpectedException(\LogicException::class);
    $account_proxy = new AccountProxy();
    $current_user = $this->prophesize(AccountInterface::class);
    $account_proxy->setAccount($current_user->reveal());
    $account_proxy->setInitialAccountId(1);
  }

}

namespace Drupal\Core\Session;

if (!function_exists('drupal_get_user_timezone')) {
  function drupal_get_user_timezone() {
    return date_default_timezone_get();
  }
}
