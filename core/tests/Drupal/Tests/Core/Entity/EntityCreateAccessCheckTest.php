<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityCreateAccessCheckTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityCreateAccessCheck;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityCreateAccessCheck
 *
 * @group Access
 * @group Entity
 */
class EntityCreateAccessCheckTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  public $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Provides test data for testAccess.
   *
   * @return array
   */
  public function providerTestAccess() {
    $no_access = AccessResult::neutral()->cachePerPermissions();
    $access = AccessResult::allowed()->cachePerPermissions();
    $no_access_due_to_errors = AccessResult::neutral();

    return array(
      array('', 'entity_test', $no_access, $no_access),
      array('', 'entity_test', $access, $access),
      array('test_entity', 'entity_test:test_entity', $access, $access),
      array('test_entity', 'entity_test:test_entity', $no_access, $no_access),
      array('test_entity', 'entity_test:{bundle_argument}', $access, $access),
      array('test_entity', 'entity_test:{bundle_argument}', $no_access, $no_access),
      array('', 'entity_test:{bundle_argument}', $no_access, $no_access_due_to_errors),
      // When the bundle is not provided, access should be denied even if the
      // access control handler would allow access.
      array('', 'entity_test:{bundle_argument}', $access, $no_access_due_to_errors),
    );
  }

  /**
   * Tests the method for checking access to routes.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess($entity_bundle, $requirement, $access, $expected) {
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    // Don't expect a call to the access control handler when we have a bundle
    // argument requirement but no bundle is provided.
    if ($entity_bundle || strpos($requirement, '{') === FALSE) {
      $access_control_handler = $this->getMock('Drupal\Core\Entity\EntityAccessControlHandlerInterface');
      $access_control_handler->expects($this->once())
        ->method('createAccess')
        ->with($entity_bundle)
        ->will($this->returnValue($access));

      $entity_manager->expects($this->any())
        ->method('getAccessControlHandler')
        ->will($this->returnValue($access_control_handler));
    }

    $applies_check = new EntityCreateAccessCheck($entity_manager);

    $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();
    $route->expects($this->any())
      ->method('getRequirement')
      ->with('_entity_create_access')
      ->will($this->returnValue($requirement));

    $raw_variables = new ParameterBag();
    if ($entity_bundle) {
      $raw_variables->set('bundle_argument', $entity_bundle);
    }

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->any())
      ->method('getRawParameters')
      ->will($this->returnValue($raw_variables));

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertEquals($expected, $applies_check->access($route, $route_match, $account));
  }

}
