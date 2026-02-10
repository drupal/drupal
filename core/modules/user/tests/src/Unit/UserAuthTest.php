<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Authentication\Provider\Cookie;
use Drupal\user\Entity\User;
use Drupal\user\UserAuth;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\user\UserAuth.
 */
#[CoversClass(UserAuth::class)]
#[Group('user')]
class UserAuthTest extends UnitTestCase {

  /**
   * The mock user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userStorage;

  /**
   * The mocked password service.
   *
   * @var \Drupal\Core\Password\PasswordInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $passwordService;

  /**
   * The user auth object under test.
   *
   * @var \Drupal\user\UserAuth
   */
  protected $userAuth;

  /**
   * The test username.
   *
   * @var string
   */
  protected $username = 'test_user';

  /**
   * The test password.
   *
   * @var string
   */
  protected $password = 'password';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->userStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\Stub $entity_type_manager */
    $entity_type_manager = $this->createStub(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    $this->passwordService = $this->createStub(PasswordInterface::class);

    $this->userAuth = new UserAuth($entity_type_manager, $this->passwordService);
  }

  /**
   * Tests failing authentication with missing credential parameters.
   */
  #[DataProvider('providerTestAuthenticateWithMissingCredentials')]
  public function testAuthenticateWithMissingCredentials($username, $password): void {
    $this->userStorage->expects($this->never())
      ->method('loadByProperties');

    $this->assertFalse($this->userAuth->authenticate($username, $password));
  }

  /**
   * Data provider for testAuthenticateWithMissingCredentials().
   *
   * @return array
   *   An array of test data.
   */
  public static function providerTestAuthenticateWithMissingCredentials() {
    return [
      [NULL, NULL],
      [NULL, ''],
      ['', NULL],
      ['', ''],
    ];
  }

  /**
   * Tests the authenticate method with no account returned.
   */
  public function testAuthenticateWithNoAccountReturned(): void {
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([]);

    $this->assertFalse($this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the authenticate method with an incorrect password.
   */
  public function testAuthenticateWithIncorrectPassword(): void {
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$this->createStub(User::class)]);

    $this->passwordService
      ->method('check')
      ->willReturn(FALSE);

    $this->assertFalse($this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the authenticate method with a correct password.
   */
  public function testAuthenticateWithCorrectPassword(): void {
    $testUser = $this->createPartialMock(User::class, ['id', 'getPassword']);
    $testUser->expects($this->once())
      ->method('id')
      ->willReturn(1);

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$testUser]);

    $this->passwordService
      ->method('check')
      ->willReturn(TRUE);

    $this->assertSame(1, $this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the authenticate method with a correct password.
   *
   * We discovered in https://www.drupal.org/node/2563751 that logging in with a
   * password that is literally "0" was not possible. This test ensures that
   * this regression can't happen again.
   */
  public function testAuthenticateWithZeroPassword(): void {
    $testUser = $this->createPartialMock(User::class, ['id', 'getPassword']);
    $testUser->expects($this->once())
      ->method('id')
      ->willReturn(2);

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$testUser]);

    $this->passwordService
      ->method('check')
      ->with(0, 0)
      ->willReturn(TRUE);

    $this->assertSame(2, $this->userAuth->authenticate($this->username, 0));
  }

  /**
   * Tests the authenticate method with a correct password & new password hash.
   */
  public function testAuthenticateWithCorrectPasswordAndNewPasswordHash(): void {
    $testUser = $this->createPartialMock(User::class, ['id', 'setPassword', 'save', 'getPassword']);
    $testUser->expects($this->once())
      ->method('id')
      ->willReturn(1);
    $testUser->expects($this->once())
      ->method('setPassword')
      ->with($this->password);
    $testUser->expects($this->once())
      ->method('save');

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$testUser]);

    $this->passwordService
      ->method('check')
      ->willReturn(TRUE);
    $this->passwordService
      ->method('needsRehash')
      ->willReturn(TRUE);

    $this->assertSame(1, $this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the auth that ends in a redirect from subdomain to TLD.
   */
  public function testAddCheckToUrlForTrustedRedirectResponse(): void {
    $this->userStorage->expects($this->never())
      ->method('loadByProperties');

    $site_domain = 'site.com';
    $frontend_url = "https://$site_domain";
    $backend_url = "https://api.$site_domain";
    $request = Request::create($backend_url);
    $response = new TrustedRedirectResponse($frontend_url);

    $request_context = $this->createStub(RequestContext::class);
    $request_context
      ->method('getCompleteBaseUrl')
      ->willReturn($backend_url);

    $container = new ContainerBuilder();
    $container->set('router.request_context', $request_context);
    \Drupal::setContainer($container);

    $session_mock = $this->createMock(SessionInterface::class);
    $session_mock
      ->expects($this->once())
      ->method('has')
      ->with('check_logged_in')
      ->willReturn(TRUE);
    $session_mock
      ->expects($this->once())
      ->method('remove')
      ->with('check_logged_in');

    $event = new ResponseEvent(
      $this->createStub(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );

    $request
      ->setSession($session_mock);

    $cookie = new Cookie(
      $this->createStub(SessionConfigurationInterface::class),
      $this->createStub(Connection::class),
      $this->createStub(MessengerInterface::class),
    );
    $cookie->addCheckToUrl($event);

    $this->assertSame("$frontend_url?check_logged_in=1", $response->getTargetUrl());
  }

  /**
   * Tests the auth that ends in a redirect from subdomain with a fragment to TLD.
   */
  public function testAddCheckToUrlForTrustedRedirectResponseWithFragment(): void {
    $this->userStorage->expects($this->never())
      ->method('loadByProperties');

    $site_domain = 'site.com';
    $frontend_url = "https://$site_domain";
    $backend_url = "https://api.$site_domain";
    $request = Request::create($backend_url);
    $response = new TrustedRedirectResponse($frontend_url . '#a_fragment');

    $request_context = $this->createStub(RequestContext::class);
    $request_context
      ->method('getCompleteBaseUrl')
      ->willReturn($backend_url);

    $container = new ContainerBuilder();
    $container->set('router.request_context', $request_context);
    \Drupal::setContainer($container);

    $session_mock = $this->createMock(SessionInterface::class);
    $session_mock
      ->expects($this->once())
      ->method('has')
      ->with('check_logged_in')
      ->willReturn(TRUE);
    $session_mock
      ->expects($this->once())
      ->method('remove')
      ->with('check_logged_in');

    $event = new ResponseEvent(
      $this->createStub(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );

    $request
      ->setSession($session_mock);

    $cookie = new Cookie(
      $this->createStub(SessionConfigurationInterface::class),
      $this->createStub(Connection::class),
      $this->createStub(MessengerInterface::class),
    );
    $cookie->addCheckToUrl($event);

    $this->assertSame("$frontend_url?check_logged_in=1#a_fragment", $response->getTargetUrl());
  }

}
