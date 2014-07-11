<?php

/**
 * @file
 * Contains \Drupal\quickedit\Tests\Access\EditEntityAccessCheckTest.
 */

namespace Drupal\quickedit\Tests\Access;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\quickedit\Access\EditEntityAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityInterface;

/**
 * @coversDefaultClass \Drupal\quickedit\Access\EditEntityAccessCheck
 * @group quickedit
 */
class EditEntityAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\quickedit\Access\EditEntityAccessCheck
   */
  protected $editAccessCheck;

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorage;

  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $this->entityStorage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');

    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($this->entityStorage));

    $this->editAccessCheck = new EditEntityAccessCheck($this->entityManager);
  }

  /**
   * Provides test data for testAccess().
   *
   * @see \Drupal\quickedit\Tests\quickedit\Access\EditEntityAccessCheckTest::testAccess()
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
    $request = new Request();

    // Prepare the request to be valid.
    $request->attributes->set('entity', $entity);
    $request->attributes->set('entity_type', 'test_entity');

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $access = $this->editAccessCheck->access($request, $account);
    $this->assertSame($expected_result, $access);
  }

  /**
   * Tests the access method with an undefined entity type.
   */
  public function testAccessWithUndefinedEntityType() {
    $request = new Request();
    $request->attributes->set('entity_type', 'non_valid');

    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->with('non_valid')
      ->will($this->returnValue(NULL));

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, $account));
  }

  /**
   * Tests the access method with a non existing entity.
   */
  public function testAccessWithNotExistingEntity() {
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', 1);

    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->with('entity_test')
      ->will($this->returnValue(array('id' => 'entity_test')));

    $this->entityStorage->expects($this->once())
      ->method('load')
      ->with(1)
      ->will($this->returnValue(NULL));

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, $account));
  }

}
