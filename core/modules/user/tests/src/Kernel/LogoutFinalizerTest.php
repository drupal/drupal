<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\LoginFinalizer;
use Drupal\user\LogoutFinalizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests logout finalization.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class LogoutFinalizerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The test user ID.
   */
  protected int|string|null $testUserId;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'user_hooks_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests logout.
   */
  public function testLogout(): void {
    // Create a user.
    $user = $this->createUser();

    // Create a mock session manager.
    $sessionManager = $this->createMock(SessionManagerInterface::class);
    $sessionManager->expects($this->once())
      ->method('destroy');
    $this->container->set('session_manager', $sessionManager);

    // Log the user in first.
    $this->container->get(LoginFinalizer::class)->finalizeLogin($user);

    // Handle the logout.
    $this->container->get(LogoutFinalizer::class)->finalizeLogout();

    // Assert our user logout hook was called.
    $this->assertEquals($user->id(), $this->testUserId);

    // Assert the session is now anonymous.
    /** @var \Drupal\Core\Session\AccountProxyInterface $accountProxy */
    $accountProxy = $this->container->get('current_user');
    $this->assertInstanceOf(AnonymousUserSession::class, $accountProxy->getAccount());
  }

  /**
   * Implements hook_user_logout().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that just logged out.
   */
  #[Hook('user_logout')]
  public function userLogout(AccountInterface $account): void {
    if (!isset($this->testUserId)) {
      $this->testUserId = $account->id();
    }
  }

}
