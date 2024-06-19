<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * @coversDefaultClass \Drupal\Core\Session\UserSession
 * @group Session
 */
class UserSessionTest extends UnitTestCase {

  /**
   * Setups a user session for the test.
   *
   * @param array $rids
   *   The rids of the user.
   * @param bool $authenticated
   *   TRUE if it is an authenticated user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The created user session.
   */
  protected function createUserSession(array $rids = [], $authenticated = FALSE) {
    array_unshift($rids, $authenticated ? RoleInterface::AUTHENTICATED_ID : RoleInterface::ANONYMOUS_ID);
    return new UserSession(['roles' => $rids]);
  }

  /**
   * Tests the has permission method.
   *
   * @see \Drupal\Core\Session\UserSession::hasPermission()
   */
  public function testHasPermission(): void {
    $user = $this->createUserSession();

    $permission_checker = $this->prophesize('Drupal\Core\Session\PermissionCheckerInterface');
    $permission_checker->hasPermission('example permission', $user)->willReturn(TRUE);
    $permission_checker->hasPermission('another example permission', $user)->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('permission_checker', $permission_checker->reveal());
    \Drupal::setContainer($container);

    $this->assertTrue($user->hasPermission('example permission'));
    $this->assertFalse($user->hasPermission('another example permission'));
  }

  /**
   * Tests the method getRoles exclude or include locked roles based in param.
   *
   * @covers ::getRoles
   * @todo Move roles constants to a class/interface
   */
  public function testUserGetRoles(): void {
    $user = $this->createUserSession(['role_two'], TRUE);
    $this->assertEquals([RoleInterface::AUTHENTICATED_ID, 'role_two'], $user->getRoles());
    $this->assertEquals(['role_two'], $user->getRoles(TRUE));
  }

  /**
   * Tests the hasRole method.
   *
   * @covers ::hasRole
   */
  public function testHasRole(): void {
    $user1 = $this->createUserSession(['role_one']);
    $user2 = $this->createUserSession(['role_one', 'role_two']);
    $user3 = $this->createUserSession(['role_two'], TRUE);
    $user4 = $this->createUserSession();

    $this->assertTrue($user1->hasRole('role_one'));
    $this->assertFalse($user2->hasRole('no role'));
    $this->assertTrue($user3->hasRole(RoleInterface::AUTHENTICATED_ID));
    $this->assertFalse($user3->hasRole(RoleInterface::ANONYMOUS_ID));
    $this->assertTrue($user4->hasRole(RoleInterface::ANONYMOUS_ID));
  }

  /**
   * Tests deprecation when permission is not a string.
   *
   * @covers ::hasPermission
   * @group legacy
   */
  public function testHasPermissionLegacy(): void {
    $this->expectDeprecation('Calling Drupal\Core\Session\UserSession::hasPermission() with a $permission parameter of type other than string is deprecated in drupal:10.3.0 and will cause an error in drupal:11.0.0. See https://www.drupal.org/node/3411485');
    $this->assertFalse((new UserSession())->hasPermission(NULL));
    $this->expectDeprecation('Calling Drupal\user\Entity\User::hasPermission() with a $permission parameter of type other than string is deprecated in drupal:10.3.0 and will cause an error in drupal:11.0.0. See https://www.drupal.org/node/3411485');
    $reflection = new \ReflectionClass(User::class);
    $user = $reflection->newInstanceWithoutConstructor();
    $this->assertFalse($user->hasPermission(NULL));
  }

}
