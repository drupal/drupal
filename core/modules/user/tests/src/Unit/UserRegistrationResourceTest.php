<?php

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Drupal\user\Plugin\rest\resource\UserRegistrationResource;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Only administrators can create user accounts.
 */
if (!defined('USER_REGISTER_ADMINISTRATORS_ONLY')) {
  define('USER_REGISTER_ADMINISTRATORS_ONLY', 'admin_only');
}

/**
 * Visitors can create their own accounts.
 */
if (!defined('USER_REGISTER_VISITORS')) {
  define('USER_REGISTER_VISITORS', 'visitors');
}

/**
 * Visitors can create accounts, but they don't become active without
 * administrative approval.
 */
if (!defined('USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL')) {
  define('USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL', 'visitors_admin_approval');
}

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
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $userSettings;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->logger = $this->prophesize(LoggerInterface::class)->reveal();

    $this->userSettings = $this->prophesize(ImmutableConfig::class);

    $this->currentUser = $this->prophesize(AccountInterface::class);

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal());
    $this->reflection = new \ReflectionClass($this->testClass);
  }

  /**
   * Tests that an exception is thrown when no data provided for the account.
   */
  public function testEmptyPost() {
    $this->setExpectedException(BadRequestHttpException::class);
    $this->testClass->post(NULL);
  }

  /**
   * Tests that only new user accounts can be registered.
   */
  public function testExistedEntityPost() {
    $entity = $this->prophesize(User::class);
    $entity->isNew()->willReturn(FALSE);
    $this->setExpectedException(BadRequestHttpException::class);

    $this->testClass->post($entity->reveal());
  }

  /**
   * Tests that admin permissions are required to register a user account.
   */
  public function testRegistrationAdminOnlyPost() {

    $this->userSettings->get('register')->willReturn(USER_REGISTER_ADMINISTRATORS_ONLY);

    $this->currentUser->isAnonymous()->willReturn(TRUE);

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal());

    $entity = $this->prophesize(User::class);
    $entity->isNew()->willReturn(TRUE);

    $this->setExpectedException(AccessDeniedHttpException::class);

    $this->testClass->post($entity->reveal());
  }

  /**
   * Tests that only anonymous users can register users.
   */
  public function testRegistrationAnonymousOnlyPost() {
    $this->currentUser->isAnonymous()->willReturn(FALSE);

    $this->testClass = new UserRegistrationResource([], 'plugin_id', '', [], $this->logger, $this->userSettings->reveal(), $this->currentUser->reveal());

    $entity = $this->prophesize(User::class);
    $entity->isNew()->willReturn(TRUE);

    $this->setExpectedException(AccessDeniedHttpException::class);

    $this->testClass->post($entity->reveal());
  }

}
