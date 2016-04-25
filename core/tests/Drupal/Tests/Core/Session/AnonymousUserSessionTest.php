<?php

namespace Drupal\Tests\Core\Session;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\user\RoleInterface;

/**
 * @coversDefaultClass \Drupal\Core\Session\AnonymousUserSession
 * @group Session
 */
class AnonymousUserSessionTest extends UnitTestCase {

  /**
   * Tests the method getRoles exclude or include locked roles based in param.
   *
   * @covers ::getRoles
   * @todo Move roles constants to a class/interface
   */
  public function testUserGetRoles() {
    $anonymous_user = new AnonymousUserSession();
    $this->assertEquals(array(RoleInterface::ANONYMOUS_ID), $anonymous_user->getRoles());
    $this->assertEquals(array(), $anonymous_user->getRoles(TRUE));
  }

}
