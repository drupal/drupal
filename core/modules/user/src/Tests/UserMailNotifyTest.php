<?php

namespace Drupal\user\Tests;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests _user_mail_notify() use of user.settings.notify.*.
 *
 * @group user
 */
class UserMailNotifyTest extends EntityKernelTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Data provider for user mail testing.
   *
   * @return array
   */
  public function userMailsProvider() {
    return [
      ['cancel_confirm', ['cancel_confirm']],
      ['password_reset', ['password_reset']],
      ['status_activated', ['status_activated']],
      ['status_blocked', ['status_blocked']],
      ['status_canceled', ['status_canceled']],
      ['register_admin_created', ['register_admin_created']],
      ['register_no_approval_required', ['register_no_approval_required']],
      ['register_pending_approval', ['register_pending_approval', 'register_pending_approval_admin']]
    ];
  }

  /**
   * Tests mails are sent when notify.$op is TRUE.
   *
   * @param string $op
   *   The operation being performed on the account.
   * @param array $mail_keys
   *   The mail keys to test for.
   *
   * @dataProvider userMailsProvider
   */
  public function testUserMailsSent($op, array $mail_keys) {
    $this->config('user.settings')->set('notify.' . $op, TRUE)->save();
    $return = _user_mail_notify($op, $this->createUser());
    $this->assertTrue($return, '_user_mail_notify() returns TRUE.');
    foreach ($mail_keys as $key) {
      $filter = array('key' => $key);
      $this->assertNotEmpty($this->getMails($filter), "Mails with $key exists.");
    }
    $this->assertCount(count($mail_keys), $this->getMails(), 'The expected number of emails sent.');
  }

  /**
   * Tests mails are not sent when notify.$op is FALSE.
   *
   * @param string $op
   *   The operation being performed on the account.
   * @param array $mail_keys
   *   The mail keys to test for. Ignored by this test because we assert that no
   *   mails at all are sent.
   *
   * @dataProvider userMailsProvider
   */
  public function testUserMailsNotSent($op, array $mail_keys) {
    $this->config('user.settings')->set('notify.' . $op, FALSE)->save();
    $return = _user_mail_notify($op, $this->createUser());
    $this->assertFalse($return, '_user_mail_notify() returns FALSE.');
    $this->assertEmpty($this->getMails(), 'No emails sent by _user_mail_notify().');
  }

}
