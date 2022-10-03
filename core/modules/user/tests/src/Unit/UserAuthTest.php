<?php

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Authentication\Provider\Cookie;
use Drupal\user\UserAuth;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\user\UserAuth
 * @group user
 */
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
   * @var \Drupal\Core\Password\PasswordInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $passwordService;

  /**
   * The mock user.
   *
   * @var \Drupal\user\Entity\User|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $testUser;

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
    $this->userStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entity_type_manager */
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    $this->passwordService = $this->createMock('Drupal\Core\Password\PasswordInterface');

    $this->testUser = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->onlyMethods(['id', 'setPassword', 'save', 'getPassword'])
      ->getMock();

    $this->userAuth = new UserAuth($entity_type_manager, $this->passwordService);
  }

  /**
   * Tests failing authentication with missing credential parameters.
   *
   * @covers ::authenticate
   *
   * @dataProvider providerTestAuthenticateWithMissingCredentials
   */
  public function testAuthenticateWithMissingCredentials($username, $password) {
    $this->userStorage->expects($this->never())
      ->method('loadByProperties');

    $this->assertFalse($this->userAuth->authenticate($username, $password));
  }

  /**
   * Data provider for testAuthenticateWithMissingCredentials().
   *
   * @return array
   */
  public function providerTestAuthenticateWithMissingCredentials() {
    return [
      [NULL, NULL],
      [NULL, ''],
      ['', NULL],
      ['', ''],
    ];
  }

  /**
   * Tests the authenticate method with no account returned.
   *
   * @covers ::authenticate
   */
  public function testAuthenticateWithNoAccountReturned() {
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([]);

    $this->assertFalse($this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the authenticate method with an incorrect password.
   *
   * @covers ::authenticate
   */
  public function testAuthenticateWithIncorrectPassword() {
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$this->testUser]);

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with($this->password, $this->testUser->getPassword())
      ->willReturn(FALSE);

    $this->assertFalse($this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the authenticate method with a correct password.
   *
   * @covers ::authenticate
   */
  public function testAuthenticateWithCorrectPassword() {
    $this->testUser->expects($this->once())
      ->method('id')
      ->willReturn(1);

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$this->testUser]);

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with($this->password, $this->testUser->getPassword())
      ->willReturn(TRUE);

    $this->assertSame(1, $this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the authenticate method with a correct password.
   *
   * We discovered in https://www.drupal.org/node/2563751 that logging in with a
   * password that is literally "0" was not possible. This test ensures that
   * this regression can't happen again.
   *
   * @covers ::authenticate
   */
  public function testAuthenticateWithZeroPassword() {
    $this->testUser->expects($this->once())
      ->method('id')
      ->willReturn(2);

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$this->testUser]);

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with(0, 0)
      ->willReturn(TRUE);

    $this->assertSame(2, $this->userAuth->authenticate($this->username, 0));
  }

  /**
   * Tests the authenticate method with a correct password & new password hash.
   *
   * @covers ::authenticate
   */
  public function testAuthenticateWithCorrectPasswordAndNewPasswordHash() {
    $this->testUser->expects($this->once())
      ->method('id')
      ->willReturn(1);
    $this->testUser->expects($this->once())
      ->method('setPassword')
      ->with($this->password);
    $this->testUser->expects($this->once())
      ->method('save');

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->willReturn([$this->testUser]);

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with($this->password, $this->testUser->getPassword())
      ->willReturn(TRUE);
    $this->passwordService->expects($this->once())
      ->method('needsRehash')
      ->with($this->testUser->getPassword())
      ->willReturn(TRUE);

    $this->assertSame(1, $this->userAuth->authenticate($this->username, $this->password));
  }

  /**
   * Tests the auth that ends in a redirect from subdomain to TLD.
   */
  public function testAddCheckToUrlForTrustedRedirectResponse(): void {
    $site_domain = 'site.com';
    $frontend_url = "https://$site_domain";
    $backend_url = "https://api.$site_domain";
    $request = Request::create($backend_url);
    $response = new TrustedRedirectResponse($frontend_url);

    $request_context = $this->createMock(RequestContext::class);
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
      $this->createMock(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );

    $request
      ->setSession($session_mock);

    $this
      ->getMockBuilder(Cookie::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock()
      ->addCheckToUrl($event);

    $this->assertSame("$frontend_url?check_logged_in=1", $response->getTargetUrl());
  }

}
