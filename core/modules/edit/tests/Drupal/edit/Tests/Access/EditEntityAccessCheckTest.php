<?php

/**
 * @file
 * Contains \Drupal\edit\Tests\Access\EditEntityAccessCheckTest.
 */

namespace Drupal\edit\Tests\Access;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\edit\Access\EditEntityAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Tests the edit entity access controller.
 *
 * @group Drupal
 * @group Edit
 *
 * @see \Drupal\edit\Access\EditEntityAccessCheck
 */
class EditEntityAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\edit\Access\EditEntityAccessCheck
   */
  protected $editAccessCheck;

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked entity storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorageController;

  public static function getInfo() {
    return array(
      'name' => 'Edit entity access check test',
      'description' => 'Unit test of edit entity access check.',
      'group' => 'Edit'
    );
  }

  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $this->entityStorageController = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');

    $this->entityManager->expects($this->any())
      ->method('getStorageController')
      ->will($this->returnValue($this->entityStorageController));

    $this->editAccessCheck = new EditEntityAccessCheck($this->entityManager);
  }

  /**
   * Provides test data for testAccess().
   *
   * @see \Drupal\edit\Tests\edit\Access\EditEntityAccessCheckTest::testAccess()
   */
  public function providerTestAccess() {
    $editable_entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();
    $editable_entity->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));

    $non_editable_entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();
    $non_editable_entity->expects($this->any())
      ->method('access')
      ->will($this->returnValue(FALSE));

    $data = array();
    $data[] = array($editable_entity, AccessCheckInterface::ALLOW);
    $data[] = array($non_editable_entity, AccessCheckInterface::DENY);

    return $data;
  }

  /**
   * Tests the method for checking access to routes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A mocked entity.
   * @param bool|null $expected_result
   *   The expected result of the access call.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess(EntityInterface $entity, $expected_result) {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity' => 'TRUE'));
    $request = new Request();

    // Prepare the request to be valid.
    $request->attributes->set('entity', $entity);
    $request->attributes->set('entity_type', 'test_entity');

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $access = $this->editAccessCheck->access($route, $request, $account);
    $this->assertSame($expected_result, $access);
  }

  /**
   * Tests the access method with an undefined entity type.
   */
  public function testAccessWithUndefinedEntityType() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'non_valid');

    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->with('non_valid')
      ->will($this->returnValue(NULL));

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($route, $request, $account));
  }

  /**
   * Tests the access method with a non existing entity.
   */
  public function testAccessWithNotExistingEntity() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', 1);

    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->with('entity_test')
      ->will($this->returnValue(array('id' => 'entity_test')));

    $this->entityStorageController->expects($this->once())
      ->method('load')
      ->with(1)
      ->will($this->returnValue(NULL));

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($route, $request, $account));
  }

}
