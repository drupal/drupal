<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityCreateAccessCheckTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Entity\EntityCreateAccessCheck;
use Drupal\Tests\UnitTestCase;
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
   * Tests the appliesTo method for the access checker.
   */
  public function testAppliesTo() {
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_access = new EntityCreateAccessCheck($entity_manager);
    $this->assertEquals($entity_access->appliesTo(), array('_entity_create_access'), 'Access checker returned the expected appliesTo() array.');
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
    );
  }

  /**
   * Tests the method for checking access to routes.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess($entity_bundle, $requirement, $access, $expected) {
    $entity = $this->getMockBuilder('Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->with($this->equalTo('entity_test'))
      ->will($this->returnValue(array('entity_keys' => array('bundle' => 'type'))));

    $storage_controller = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');
    $values = $entity_bundle ? array('type' => $entity_bundle) : array();
    $storage_controller->expects($this->any())
      ->method('create')
      ->with($this->equalTo($values))
      ->will($this->returnValue($entity));

    $access_controller = $this->getMock('Drupal\Core\Entity\EntityAccessControllerInterface');
    $access_controller->expects($this->once())
      ->method('access')
      ->with($entity, 'create')
      ->will($this->returnValue($access));

    $entity_manager->expects($this->any())
      ->method('getStorageController')
      ->will($this->returnValue($storage_controller));
    $entity_manager->expects($this->any())
      ->method('getAccessController')
      ->will($this->returnValue($access_controller));

    $applies_check = new EntityCreateAccessCheck($entity_manager);

    $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();
    $route->expects($this->any())
      ->method('getRequirement')
      ->with('_entity_create_access')
      ->will($this->returnValue($requirement));

    $request = new Request();

    $this->assertEquals($expected, $applies_check->access($route, $request));
  }

}
