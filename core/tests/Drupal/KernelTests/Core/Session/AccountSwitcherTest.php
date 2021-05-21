<?php

namespace Drupal\KernelTests\Core\Session;

use Drupal\Core\Session\UserSession;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test case for account switching.
 *
 * @group Session
 */
class AccountSwitcherTest extends KernelTestBase {

  public function testAccountSwitching() {
    $session_handler = $this->container->get('session_handler.write_safe');
    $user = $this->container->get('current_user');
    $switcher = $this->container->get('account_switcher');
    $original_user = $user->getAccount();
    $original_session_saving = $session_handler->isSessionWritable();

    // Switch to user with uid 2.
    $switcher->switchTo(new UserSession(['uid' => 2]));

    // Verify that the active user has changed, and that session saving is
    // disabled.
    $this->assertEquals(2, $user->id(), 'Switched to user 2.');
    $this->assertFalse($session_handler->isSessionWritable(), 'Session saving is disabled.');

    // Perform a second (nested) user account switch.
    $switcher->switchTo(new UserSession(['uid' => 3]));
    $this->assertEquals(3, $user->id(), 'Switched to user 3.');

    // Revert to the user session that was active between the first and second
    // switch.
    $switcher->switchBack();

    // Since we are still in the account from the first switch, session handling
    // still needs to be disabled.
    $this->assertEquals(2, $user->id(), 'Reverted to user 2.');
    $this->assertFalse($session_handler->isSessionWritable(), 'Session saving still disabled.');

    // Revert to the original account which was active before the first switch.
    $switcher->switchBack();

    // Assert that the original account is active again, and that session saving
    // has been re-enabled.
    $this->assertEquals($original_user->id(), $user->id(), 'Original user correctly restored.');
    $this->assertEquals($original_session_saving, $session_handler->isSessionWritable(), 'Original session saving correctly restored.');

    // Verify that AccountSwitcherInterface::switchBack() will throw
    // an exception if there are no accounts left in the stack.
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No more accounts to revert to.');
    $switcher->switchBack();
  }

}
