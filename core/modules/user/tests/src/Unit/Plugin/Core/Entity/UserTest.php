<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Plugin\Core\Entity\UserTest.
 */

namespace Drupal\Tests\user\Unit\Plugin\Core\Entity {

use Drupal\Tests\Core\Session\UserSessionTest;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\user\Entity\User
 * @group user
 */
class UserTest extends UserSessionTest {

  /**
   * {@inheritdoc}
   */
  protected function createUserSession(array $rids = array()) {
    $user = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->setMethods(array('get', 'id'))
      ->getMock();
    $user->expects($this->any())
      ->method('id')
      // @todo Also test the uid = 1 handling.
      ->will($this->returnValue(0));
    $roles = array();
    foreach ($rids as $rid) {
      $roles[] = (object) array(
        'target_id' => $rid,
      );
    }
    $user->expects($this->any())
      ->method('get')
      ->with('roles')
      ->will($this->returnValue($roles));
    return $user;
  }

  /**
   * Tests the method getRoles exclude or include locked roles based in param.
   *
   * @see \Drupal\user\Entity\User::getRoles()
   * @covers ::getRoles
   * @todo Move roles constants to a class/interface
   */
  public function testUserGetRoles() {
    // Anonymous user.
    $user = $this->createUserSession(array(DRUPAL_ANONYMOUS_RID));
    $this->assertEquals(array(DRUPAL_ANONYMOUS_RID), $user->getRoles());
    $this->assertEquals(array(), $user->getRoles(TRUE));

    // Authenticated user.
    $user = $this->createUserSession(array(DRUPAL_AUTHENTICATED_RID));
    $this->assertEquals(array(DRUPAL_AUTHENTICATED_RID), $user->getRoles());
    $this->assertEquals(array(), $user->getRoles(TRUE));
  }

}

}

namespace {

  if (!defined('DRUPAL_ANONYMOUS_RID')) {
    /**
     * Stub Role ID for anonymous users since bootstrap.inc isn't available.
     */
    define('DRUPAL_ANONYMOUS_RID', 'anonymous');
  }
  if (!defined('DRUPAL_AUTHENTICATED_RID')) {
    /**
     * Stub Role ID for authenticated users since bootstrap.inc isn't available.
     */
    define('DRUPAL_AUTHENTICATED_RID', 'authenticated');
  }

}
