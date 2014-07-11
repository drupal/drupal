<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityAccessCheckTest.
 */

namespace Drupal\Tests\Core\Entity;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test of entity access checking system.
 *
 * @group Entity
 */
class EntityAccessCheckTest extends UnitTestCase {

  /**
   * Tests the method for checking access to routes.
   */
  public function testAccess() {
    $route = new Route('/foo', array(), array('_entity_access' => 'node.update'));
    $request = new Request();
    $node = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $access_check = new EntityAccessCheck();
    $request->attributes->set('node', $node);
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $access = $access_check->access($route, $request, $account);
    $this->assertSame(AccessCheckInterface::ALLOW, $access);
  }

}
