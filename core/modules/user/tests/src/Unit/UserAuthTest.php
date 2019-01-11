<?php

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserAuth;

/**
 * @coversDefaultClass \Drupal\user\UserAuth
 * @group user
 */
class UserAuthTest extends UnitTestCase {

  /**
   * The mock user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $userStorage;

  /**
   * The mocked password service.
   *
   * @var \Drupal\Core\Password\PasswordInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $passwordService;

  /**
   * The mock user.
   *
   * @var \Drupal\user\Entity\User|\PHPUnit_Framework_MockObject_MockObject
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
  protected function setUp() {
    $this->userStorage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entity_type_manager */
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->will($this->returnValue($this->userStorage));

    $this->passwordService = $this->getMock('Drupal\Core\Password\PasswordInterface');

    $this->testUser = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->setMethods(['id', 'setPassword', 'save', 'getPassword'])
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
      ->will($this->returnValue([]));

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
      ->will($this->returnValue([$this->testUser]));

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with($this->password, $this->testUser->getPassword())
      ->will($this->returnValue(FALSE));

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
      ->will($this->returnValue(1));

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->will($this->returnValue([$this->testUser]));

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with($this->password, $this->testUser->getPassword())
      ->will($this->returnValue(TRUE));

    $this->assertsame(1, $this->userAuth->authenticate($this->username, $this->password));
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
      ->will($this->returnValue(2));

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->will($this->returnValue([$this->testUser]));

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with(0, 0)
      ->will($this->returnValue(TRUE));

    $this->assertsame(2, $this->userAuth->authenticate($this->username, 0));
  }

  /**
   * Tests the authenticate method with a correct password and new password hash.
   *
   * @covers ::authenticate
   */
  public function testAuthenticateWithCorrectPasswordAndNewPasswordHash() {
    $this->testUser->expects($this->once())
      ->method('id')
      ->will($this->returnValue(1));
    $this->testUser->expects($this->once())
      ->method('setPassword')
      ->with($this->password);
    $this->testUser->expects($this->once())
      ->method('save');

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $this->username])
      ->will($this->returnValue([$this->testUser]));

    $this->passwordService->expects($this->once())
      ->method('check')
      ->with($this->password, $this->testUser->getPassword())
      ->will($this->returnValue(TRUE));
    $this->passwordService->expects($this->once())
      ->method('needsRehash')
      ->with($this->testUser->getPassword())
      ->will($this->returnValue(TRUE));

    $this->assertsame(1, $this->userAuth->authenticate($this->username, $this->password));
  }

}
