<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityCreateAccessCheckTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Entity\EntityCreateAccessCheck;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the entity-create access controller.
 *
 * @group Entity
 *
 * @see \Drupal\Core\Entity\EntityCreateAccessCheck
 */
class EntityCreateAccessCheckTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  public $entityManager;

  public static function getInfo() {
    return array(
      'name' => 'Entity create access check test',
      'description' => 'Unit test of entity create access checking system.',
      'group' => 'Entity'
    );
  }

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
    return array(
      array('', 'entity_test', FALSE, AccessCheckInterface::DENY),
      array('', 'entity_test',TRUE, AccessCheckInterface::ALLOW),
      array('test_entity', 'entity_test:test_entity', TRUE, AccessCheckInterface::ALLOW),
      array('test_entity', 'entity_test:test_entity', FALSE, AccessCheckInterface::DENY),
      array('test_entity', 'entity_test:{bundle_argument}', TRUE, AccessCheckInterface::ALLOW),
      array('test_entity', 'entity_test:{bundle_argument}', FALSE, AccessCheckInterface::DENY),
      array('', 'entity_test:{bundle_argument}', FALSE, AccessCheckInterface::DENY),
      // When the bundle is not provided, access should be denied even if the
      // access controller would allow access.
      array('', 'entity_test:{bundle_argument}', TRUE, AccessCheckInterface::DENY),
    );
  }

  /**
   * Tests the method for checking access to routes.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess($entity_bundle, $requirement, $access, $expected) {
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    // Don't expect a call to the access controller when we have a bundle
    // argument requirement but no bundle is provided.
    if ($entity_bundle || strpos($requirement, '{') === FALSE) {
      $access_controller = $this->getMock('Drupal\Core\Entity\EntityAccessControllerInterface');
      $access_controller->expects($this->once())
        ->method('createAccess')
        ->with($entity_bundle)
        ->will($this->returnValue($access));

      $entity_manager->expects($this->any())
        ->method('getAccessController')
        ->will($this->returnValue($access_controller));
    }

    $applies_check = new EntityCreateAccessCheck($entity_manager);

    $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();
    $route->expects($this->any())
      ->method('getRequirement')
      ->with('_entity_create_access')
      ->will($this->returnValue($requirement));

    $request = new Request();
    $raw_variables = new ParameterBag();
    if ($entity_bundle) {
      // Add the bundle as a raw variable and an upcasted attribute.
      $request->attributes->set('bundle_argument', new \stdClass());
      $raw_variables->set('bundle_argument', $entity_bundle);
    }
    $request->attributes->set('_raw_variables', $raw_variables);

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertEquals($expected, $applies_check->access($route, $request, $account));
  }

}
