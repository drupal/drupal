<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Session\AnonymousUserSession.
 */
#[CoversClass(AnonymousUserSession::class)]
#[Group('Session')]
class AnonymousUserSessionTest extends UnitTestCase {

  /**
   * Tests the method getRoles exclude or include locked roles based in param.
   *
   * @todo Move roles constants to a class/interface
   * @legacy-covers ::getRoles
   */
  public function testUserGetRoles(): void {
    $anonymous_user = new AnonymousUserSession();
    $this->assertEquals([RoleInterface::ANONYMOUS_ID], $anonymous_user->getRoles());
    $this->assertEquals([], $anonymous_user->getRoles(TRUE));
  }

}
