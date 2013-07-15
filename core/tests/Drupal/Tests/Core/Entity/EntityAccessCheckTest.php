<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityAccessCheckTest.
 */

namespace Drupal\Tests\Core\Entity;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the entity access controller.
 *
 * @group Entity
 */
class EntityAccessCheckTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Entity access check test',
      'description' => 'Unit test of entity access checking system.',
      'group' => 'Entity'
    );
  }

  /**
   * Tests the appliesTo method for the access checker.
   */
  public function testAppliesTo() {
    $entity_access = new EntityAccessCheck();
    $this->assertEquals($entity_access->appliesTo(), array('_entity_access'), 'Access checker returned the expected appliesTo() array.');
  }

  /**
   * Tests the method for checking access to routes.
   */
  public function testAccess() {
    $route = new Route('/foo', array(), array('_entity_access' => 'node.update'));
    $request = new Request();
    $node = $this->getMockBuilder('Drupal\node\Plugin\Core\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $access_check = new EntityAccessCheck();
    $request->attributes->set('node', $node);
    $access = $access_check->access($route, $request);
    $this->assertEquals(TRUE, $access);
  }

}
