<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\UserCancellation;
use Drupal\user\UserCancellationInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Tests the user cancellation service.
 *
 * @coversDefaultClass \Drupal\user\UserCancellation
 * @group user
 */
class UserCancellationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Module handler for testing.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * Current user for testing.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Messenger for testing.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * Logger for testing.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Session for testing.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $session;

  /**
   * String translation for testing.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * User storage for testing.
   *
   * @var \Drupal\user\UserStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userStorage;

  /**
   * Entity type manager for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * ID of user for testing.
   *
   * @var int
   */
  protected $userId;

  /**
   * User entity for testing.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need system.module for Batch API.
    $this->installSchema('system', 'sequences');

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->session = $this->createMock(Session::class);
    $this->stringTranslation = $this->createMock(TranslationInterface::class);
    $this->userStorage = $this->createMock(UserStorageInterface::class);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityTypeManager */
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    /** @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject $userCancellation */
    $this->userId = 42;
    $this->user = $this->createMock(UserInterface::class);
    $this->user->expects($this->any())
      ->method('id')
      ->willReturn($this->userId);
  }

  /**
   * Test cancellation.
   *
   * @param bool $userExists
   *   Whether when the user is loaded from storage, is should be returned.
   * @param string $method
   *   Method of cancellation.
   * @param array $options
   *   Options for cancellation.
   * @param bool $assertIsBlocked
   *   Determine whether the user should be blocked.
   * @param bool $assertIsDeleted
   *   Determine whether the user should be deleted.
   * @param array $expectedEmails
   *   Array of expected email ID's.
   * @param bool $invokeHooks
   *   Whether cancellation hooks are expected to be called.
   * @param array $expectedLogs
   *   Expected log messages, without replacements.
   *
   * @covers ::cancelUser
   * @dataProvider providerTestCancel
   */
  public function testCancel($userExists, $method, array $options, $assertIsBlocked, $assertIsDeleted, array $expectedEmails, $invokeHooks, array $expectedLogs) {
    $this->user->expects($this->any())
      ->method('getAccountName')
      ->will($this->returnValue('testusername'));
    $this->user->expects($this->any())
      ->method('getEmail')
      ->will($this->returnValue('test@email'));

    $this->entityTypeManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->will($this->returnValue($this->userStorage));

    $this->userStorage->expects($this->any())
      ->method('load')
      ->with($this->userId)
      ->will($this->returnValue($userExists ? $this->user : NULL));

    $this->user->expects($assertIsBlocked ? $this->once() : $this->never())
      ->method('block')
      ->with();

    $this->user->expects($assertIsDeleted ? $this->once() : $this->never())
      ->method('delete')
      ->with();

    $this->moduleHandler->expects($invokeHooks ? $this->once() : $this->never())
      ->method('invokeAll')
      ->with('user_cancel');

    if (count($expectedLogs) === 0) {
      $this->logger->expects($this->never())
        ->method('notice');
    }
    foreach ($expectedLogs as $i => $expectedLog) {
      $this->logger->expects($this->at($i))
        ->method('notice')
        ->with($expectedLog);
    }

    // Calls to cancelUser should never emit messages.
    $this->messenger->expects($this->never())
      ->method($this->anything());

    $userCancellation = $this->newUserCancellation($this->moduleHandler, $this->currentUser, $this->logger, $this->messenger, $this->session, $this->entityTypeManager, $this->stringTranslation);

    $container = \Drupal::getContainer();
    $container->set('user.cancellation', $userCancellation);
    \Drupal::setContainer($container);

    $userCancellation->cancelUser($this->user, $method, $options);

    $this->assertCount(count($expectedEmails), $userCancellation::$mailCalls);
    foreach ($expectedEmails as $i => $expectedEmail) {
      $this->assertEquals($expectedEmail, $userCancellation::$mailCalls[$i][1]);
    }
  }

  /**
   * Test data for testCancel.
   *
   * @return array
   *   Data for testing.
   */
  public function providerTestCancel() {
    $data = [];
    $data['blocked no notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_BLOCK,
      [],
      TRUE,
      FALSE,
      [],
      TRUE,
      ['Blocked user: %name %email.'],
    ];
    $data['blocked with notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_BLOCK,
      ['user_cancel_notify' => TRUE],
      TRUE,
      FALSE,
      ['status_blocked'],
      TRUE,
      ['Blocked user: %name %email.'],
    ];
    $data['blocked and unpublish no notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_BLOCK_AND_UNPUBLISH,
      [],
      TRUE,
      FALSE,
      [],
      TRUE,
      ['Blocked user: %name %email.'],
    ];
    $data['blocked and unpublish with notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_BLOCK_AND_UNPUBLISH,
      ['user_cancel_notify' => TRUE],
      TRUE,
      FALSE,
      ['status_blocked'],
      TRUE,
      ['Blocked user: %name %email.'],
    ];
    $data['reassign anonymous no notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_REASSIGN_ANONYMOUS,
      [],
      FALSE,
      TRUE,
      [],
      TRUE,
      ['Deleted user: %name %email.'],
    ];
    $data['reassign anonymous with notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_REASSIGN_ANONYMOUS,
      ['user_cancel_notify' => TRUE],
      FALSE,
      TRUE,
      ['status_canceled'],
      TRUE,
      ['Deleted user: %name %email.'],
    ];
    $data['delete no notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_DELETE,
      [],
      FALSE,
      TRUE,
      [],
      FALSE,
      ['Deleted user: %name %email.'],
    ];
    $data['delete with notification'] = [
      TRUE,
      UserCancellationInterface::METHOD_DELETE,
      ['user_cancel_notify' => TRUE],
      FALSE,
      TRUE,
      ['status_canceled'],
      FALSE,
      ['Deleted user: %name %email.'],
    ];
    $data['random method'] = [
      TRUE,
      'blah',
      [],
      FALSE,
      FALSE,
      [],
      TRUE,
      [],
    ];
    $data['user is deleted'] = [
      FALSE,
      'blah',
      [],
      FALSE,
      FALSE,
      [],
      TRUE,
      ['User %user_id deleted before cancellation could complete.'],
    ];
    return $data;
  }

  /**
   * Create a new user cancellation instance for testing.
   *
   * Remove this override when current non-injectables are converted.
   *
   * @return \Drupal\user\UserCancellation
   *   A new UserCancellation object.
   */
  protected function newUserCancellation($moduleHandler, $currentUser, $logger, $messenger, $session, $entityTypeManager, $stringTranslation) {
    return new class($moduleHandler, $currentUser, $logger, $messenger, $session, $entityTypeManager, $stringTranslation) extends UserCancellation {

      /**
       * Records call to mailer, preventing mails from being sent.
       *
       * @var array
       */
      static $mailCalls = [];

      /**
       * {@inheritdoc}
       */
      protected function mail(UserInterface $user, $operation) {
        static::$mailCalls[] = func_get_args();
      }

    };
  }

}
