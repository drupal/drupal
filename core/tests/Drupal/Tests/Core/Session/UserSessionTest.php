<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests Drupal\Core\Session\UserSession.
 */
#[CoversClass(UserSession::class)]
#[Group('Session')]
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
   * @todo Move roles constants to a class/interface
   * @legacy-covers ::getRoles
   */
  public function testUserGetRoles(): void {
    $user = $this->createUserSession(['role_two'], TRUE);
    $this->assertEquals([RoleInterface::AUTHENTICATED_ID, 'role_two'], $user->getRoles());
    $this->assertEquals(['role_two'], $user->getRoles(TRUE));
  }

  /**
   * Tests the hasRole method.
   *
   * @legacy-covers ::hasRole
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
   * Tests the name property deprecation.
   *
   * @legacy-covers ::__get
   * @legacy-covers ::__isset
   * @legacy-covers ::__set
   */
  #[IgnoreDeprecations]
  public function testNamePropertyDeprecation(): void {
    $user = new UserSession([
      'name' => 'test',
    ]);
    $this->expectDeprecation('Getting the name property is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Session\UserSession::getAccountName() instead. See https://www.drupal.org/node/3513856');
    self::assertEquals($user->name, $user->getAccountName());
    $this->expectDeprecation('Checking for the name property is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Session\UserSession::getAccountName() instead. See https://www.drupal.org/node/3513856');
    self::assertTrue(isset($user->name));

    // Test setting the name property.
    $this->expectDeprecation('Setting the name property is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Set the name via the constructor when creating the UserSession instance. See https://www.drupal.org/node/3513856');
    $user->name = 'test new';
    $this->assertEquals('test new', $user->getAccountName());

    // Verify protected properties cannot be accessed.
    $this->expectExceptionMessage('Cannot access protected property mail in Drupal\Core\Session\UserSession');
    $user->mail;

    // Verify dynamic properties can be set and accessed.
    $user->foo = 'bar';
    $this->assertEquals('bar', $user->foo);
  }

}
