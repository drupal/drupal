<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Drupal\user\Plugin\rest\resource\UserRegistrationResource;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tests User Registration REST resource.
 *
 * @coversDefaultClass \Drupal\user\Plugin\rest\resource\UserRegistrationResource
 * @group user
 */
class UserRegistrationResourceTest extends UnitTestCase {

  const ERROR_MESSAGE = "Unprocessable Entity: validation failed.\nproperty_path: message\nproperty_path_2: message_2\n";

  /**
   * Class to be tested.
   *
   * @var \Drupal\user\Plugin\rest\resource\UserRegistrationResource
   */
  protected $testClass;

  /**
   * A reflection of self::$testClass.
   *
   * @var \ReflectionClass
   */
  protected $reflection;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->prophesize(LoggerInterface::class)->reveal();

    $this->userSettings = $this->prophesize(ImmutableConfig::class);

    $this->currentUser = $this->prophesize(AccountInterface::class);

    $this->passwordGenerator = $this->prophesize(PasswordGeneratorInterface::class)->reveal();

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal(), $this->passwordGenerator);
    $this->reflection = new \ReflectionClass($this->testClass);
  }

  /**
   * Tests that an exception is thrown when no data provided for the account.
   */
  public function testEmptyPost(): void {
    $this->expectException(BadRequestHttpException::class);
    $this->testClass->post(NULL);
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

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal(), $this->passwordGenerator);

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

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal(), $this->passwordGenerator);

    $entity = $this->prophesize(User::class);
    $entity->isNew()->willReturn(TRUE);

    $this->expectException(AccessDeniedHttpException::class);

    $this->testClass->post($entity->reveal());
  }

  /**
   * Tests the deprecation messages.
   *
   * @covers ::__construct
   *
   * @group legacy
   */
  public function testDeprecations(): void {
    $this->expectDeprecation('Calling Drupal\user\Plugin\rest\resource\UserRegistrationResource::__construct() without the $password_generator argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3405799');
    $this->expectException(BadRequestHttpException::class);

    $container = new ContainerBuilder();
    $password_generator = $this->prophesize(PasswordGeneratorInterface::class);
    $container->set('password_generator', $password_generator->reveal());
    \Drupal::setContainer($container);

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal());
    $this->testClass->post(NULL);
  }

}
