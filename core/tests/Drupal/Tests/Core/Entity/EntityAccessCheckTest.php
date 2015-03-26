<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityAccessCheckTest.
 */

namespace Drupal\Tests\Core\Entity;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test of entity access checking system.
 *
 * @group Access
 * @group Entity
 */
class EntityAccessCheckTest extends UnitTestCase {

  /**
   * Tests the method for checking access to routes.
   */
  public function testAccess() {
    $route = new Route('/foo', array(), array('_entity_access' => 'node.update'));
    $upcasted_arguments = new ParameterBag();
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->once())
      ->method('getParameters')
      ->will($this->returnValue($upcasted_arguments));
    $node = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node->expects($this->any())
      ->method('access')
      ->will($this->returnValue(AccessResult::allowed()->cachePerPermissions()));
    $access_check = new EntityAccessCheck();
    $upcasted_arguments->set('node', $node);
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $access = $access_check->access($route, $route_match, $account);
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), $access);
  }

}
