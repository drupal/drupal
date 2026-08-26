<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;

/**
 * Tests _user_mail_notify() use of user.settings.notify.*.
 *
 * @todo Remove this test together with _user_mail_notify().
 */
#[Group('user')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class UserMailNotifyTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'language',
  ];

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Data provider for user mail testing.
   *
   * @return array
   *   An array of operations and the mail keys they should send.
   */
  public static function userMailsProvider(): array {
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
   */
  #[DataProvider('userMailsProvider')]
  public function testUserMailsSent(string $op, array $mail_keys): void {
    $this->installConfig('user');
    $this->config('system.site')->set('mail', 'test@example.com')->save();
    $this->config('user.settings')->set('notify.' . $op, TRUE)->save();
    $return = _user_mail_notify($op, $this->createUser());
    $this->assertTrue($return);
    foreach ($mail_keys as $key) {
      $filter = ['key' => $key];
      $this->assertNotEmpty($this->getMails($filter));
    }
    $this->assertSameSize($mail_keys, $this->getMails());
  }

  /**
   * Tests mails are not sent when notify.$op is FALSE.
   *
   * @param string $op
   *   The operation being performed on the account.
   * @param array $mail_keys
   *   The mail keys to test for.
   */
  #[DataProvider('userMailsProvider')]
  public function testUserMailsNotSent(string $op, array $mail_keys): void {
    $this->installConfig('user');
    $this->config('user.settings')->set('notify.' . $op, FALSE)->save();
    $return = _user_mail_notify($op, $this->createUser());
    $this->assertNull($return);
    $this->assertEmpty($this->getMails());
  }

  /**
   * Tests mails are not sent when the account has no email address.
   *
   * @param string $op
   *   The operation being performed on the account.
   * @param array $mail_keys
   *   The mail keys to test for.
   */
  #[DataProvider('userMailsProvider')]
  public function testUserMailsWithoutAccountEmail(string $op, array $mail_keys): void {
    $this->installConfig('user');
    $this->config('user.settings')->set('notify.' . $op, TRUE)->save();

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('log');
    /** @var \Drupal\Core\Logger\LoggerChannelFactory $logger_factory */
    $logger_factory = $this->container->get('logger.factory');
    $logger_factory->get('user')
      ->addLogger($logger);

    $return = _user_mail_notify($op, $this->createUser([], NULL, FALSE, [
      'mail' => NULL,
    ]));

    $this->assertNull($return);
    if ($op == 'register_pending_approval') {
      // The register_pending_approval op will cause an email to be sent to the
      // site address.
      $this->assertCount(1, $this->getMails());
    }
    else {
      $this->assertEmpty($this->getMails());
    }
  }

}
