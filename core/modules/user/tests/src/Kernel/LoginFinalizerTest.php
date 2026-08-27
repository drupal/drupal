<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\LoginFinalizer;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests login finalization.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class LoginFinalizerTest extends KernelTestBase {

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

    // Create a stub time service.
    $time = $this->createStub(TimeInterface::class);
    $time->method('getRequestTime')
      ->willReturn(1234567890);
    $this->container->set('datetime.time', $time);
  }

  /**
   * Tests login.
   */
  public function testLogin(): void {
    // Create a user.
    $user = $this->createUser();

    // Get the session.
    $session = $this->container->get('request_stack')->getSession();

    // Assert no user is logged in.
    $this->assertFalse($session->has('uid'));

    // Handle the login.
    $finalize = $this->container->get(LoginFinalizer::class);
    $finalize->finalizeLogin($user);

    // Assert our user is logged in.
    $this->assertEquals($user->id(), $session->get('uid'));
    $this->assertTrue($session->get('check_logged_in'));

    // Assert last login time is set.
    $user = User::load($user->id());
    $this->assertEquals(1234567890, $user->getLastLoginTime());

    // Assert our user login hook was called.
    $this->assertEquals($user->id(), $this->testUserId);
  }

  /**
   * Implements hook_user_login().
   *
   * @param \Drupal\user\UserInterface $account
   *   The user object on which the operation was just performed.
   */
  #[Hook('user_login')]
  public function userLogin(UserInterface $account): void {
    if (!isset($this->testUserId)) {
      $this->testUserId = $account->id();
    }
  }

}
