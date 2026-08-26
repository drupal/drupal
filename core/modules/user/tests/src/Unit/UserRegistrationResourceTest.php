<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Drupal\user\NotificationHandler;
use Drupal\user\Plugin\rest\resource\UserRegistrationResource;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tests User Registration REST resource.
 */
#[CoversClass(UserRegistrationResource::class)]
#[Group('user')]
class UserRegistrationResourceTest extends UnitTestCase {

  /**
   * Class to be tested.
   *
   * @var \Drupal\user\Plugin\rest\resource\UserRegistrationResource
   */
  protected $testClass;

  /**
   * A user settings config instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userSettings;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The password generator.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $passwordGenerator;

  /**
   * The user mail handler.
   */
  protected NotificationHandler&Stub $notificationHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->prophesize(LoggerInterface::class)->reveal();

    $this->userSettings = $this->prophesize(ImmutableConfig::class);

    $this->currentUser = $this->prophesize(AccountInterface::class);

    $this->passwordGenerator = $this->prophesize(PasswordGeneratorInterface::class)->reveal();

    $this->notificationHandler = $this->createStub(NotificationHandler::class);

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal(), $this->passwordGenerator, $this->notificationHandler);
  }

  /**
   * Tests that an exception is thrown when no data provided for the account.
   */
  public function testEmptyPost(): void {
    $this->expectException(BadRequestHttpException::class);
    $this->testClass->post();
  }

  /**
   * Tests that only new user accounts can be registered.
   */
  public function testExistedEntityPost(): void {
    $entity = $this->prophesize(User::class);
    $entity->isNew()->willReturn(FALSE);
    $this->expectException(BadRequestHttpException::class);

    $this->testClass->post($entity->reveal());
  }

  /**
   * Tests that admin permissions are required to register a user account.
   */
  public function testRegistrationAdminOnlyPost(): void {

    $this->userSettings->get('register')->willReturn(UserInterface::REGISTER_ADMINISTRATORS_ONLY);

    $this->currentUser->isAnonymous()->willReturn(TRUE);

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal(), $this->passwordGenerator, $this->notificationHandler);

    $entity = $this->prophesize(User::class);
    $entity->isNew()->willReturn(TRUE);

    $this->expectException(AccessDeniedHttpException::class);

    $this->testClass->post($entity->reveal());
  }

  /**
   * Tests that only anonymous users can register users.
   */
  public function testRegistrationAnonymousOnlyPost(): void {
    $this->currentUser->isAnonymous()->willReturn(FALSE);

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal(), $this->passwordGenerator, $this->notificationHandler);

    $entity = $this->prophesize(User::class);
    $entity->isNew()->willReturn(TRUE);

    $this->expectException(AccessDeniedHttpException::class);

    $this->testClass->post($entity->reveal());
  }

}
