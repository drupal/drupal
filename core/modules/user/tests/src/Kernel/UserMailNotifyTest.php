<?php

namespace Drupal\Tests\user\Kernel;

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
      'cancel confirm notification' => [
        'cancel_confirm',
        ['cancel_confirm'],
      ],
      'password reset notification' => [
        'password_reset',
        ['password_reset'],
      ],
      'status activated notification' => [
        'status_activated',
        ['status_activated'],
      ],
      'status blocked notification' => [
        'status_blocked',
        ['status_blocked'],
      ],
      'status canceled notification' => [
        'status_canceled',
        ['status_canceled'],
      ],
      'register admin created notification' => [
        'register_admin_created',
        ['register_admin_created'],
      ],
      'register no approval required notification' => [
        'register_no_approval_required',
        ['register_no_approval_required'],
      ],
      'register pending approval notification' => [
        'register_pending_approval',
        ['register_pending_approval', 'register_pending_approval_admin'],
      ],
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
    $this->assertTrue($return);
    foreach ($mail_keys as $key) {
      $filter = ['key' => $key];
      $this->assertNotEmpty($this->getMails($filter));
    }
    $this->assertCount(count($mail_keys), $this->getMails());
  }

  /**
   * Tests mails are not sent when notify.$op is FALSE.
   *
   * @param string $op
   *   The operation being performed on the account.
   *
   * @dataProvider userMailsProvider
   */
  public function testUserMailsNotSent($op) {
    $this->config('user.settings')->set('notify.' . $op, FALSE)->save();
    $return = _user_mail_notify($op, $this->createUser());
    $this->assertFalse($return);
    $this->assertEmpty($this->getMails());
  }

}
