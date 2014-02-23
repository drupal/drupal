<?php

/**
 * @file
 * Contains \Drupal\edit\Tests\Access\EditEntityFieldAccessCheckTest.
 */

namespace Drupal\edit\Tests\Access;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\edit\Access\EditEntityFieldAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityInterface;

/**
 * Tests the edit entity field access controller.
 *
 * @group Drupal
 * @group Edit
 *
 * @see \Drupal\edit\Access\EditEntityFieldAccessCheck
 */
class EditEntityFieldAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\edit\Access\EditEntityFieldAccessCheck
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
      'name' => 'Edit entity field access check test',
      'description' => 'Unit test of edit entity field access check.',
      'group' => 'Edit'
    );
  }

  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $this->entityStorageController = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');

    $this->entityManager->expects($this->any())
      ->method('getStorageController')
      ->will($this->returnValue($this->entityStorageController));

    $this->editAccessCheck = new EditEntityFieldAccessCheck($this->entityManager);
  }

  /**
   * Provides test data for testAccess().
   *
   * @see \Drupal\edit\Tests\edit\Access\EditEntityFieldAccessCheckTest::testAccess()
   */
  public function providerTestAccess() {
    $editable_entity = $this->createMockEntity();
    $editable_entity->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));

    $non_editable_entity = $this->createMockEntity();
    $non_editable_entity->expects($this->any())
      ->method('access')
      ->will($this->returnValue(FALSE));

    $field_with_access = $this->getMockBuilder('Drupal\field\Entity\FieldConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_with_access->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $field_without_access = $this->getMockBuilder('Drupal\field\Entity\FieldConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_without_access->expects($this->any())
      ->method('access')
      ->will($this->returnValue(FALSE));

    $data = array();
    $data[] = array($editable_entity, $field_with_access, AccessCheckInterface::ALLOW);
    $data[] = array($non_editable_entity, $field_with_access, AccessCheckInterface::DENY);
    $data[] = array($editable_entity, $field_without_access, AccessCheckInterface::DENY);
    $data[] = array($non_editable_entity, $field_without_access, AccessCheckInterface::DENY);

    return $data;
  }

  /**
   * Tests the method for checking access to routes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A mocked entity.
   * @param \Drupal\field\FieldConfigInterface $field
   *   A mocked field.
   * @param bool|null $expected_result
   *   The expected result of the access call.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess(EntityInterface $entity, FieldConfigInterface $field = NULL, $expected_result) {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();

    $entity_with_field = clone $entity;
    $entity_with_field->expects($this->any())
      ->method('get')
      ->with('valid')
      ->will($this->returnValue($field));
    $entity_with_field->expects($this->once())
      ->method('hasTranslation')
      ->with(Language::LANGCODE_NOT_SPECIFIED)
      ->will($this->returnValue(TRUE));

    // Prepare the request to be valid.
    $request->attributes->set('entity_type', 'test_entity');
    $request->attributes->set('entity', $entity_with_field);
    $request->attributes->set('field_name', 'valid');
    $request->attributes->set('langcode', Language::LANGCODE_NOT_SPECIFIED);

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $access = $this->editAccessCheck->access($route, $request, $account);
    $this->assertSame($expected_result, $access);
  }

  /**
   * Tests the access method with an undefined entity type.
   */
  public function testAccessWithUndefinedEntityType() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
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

  /**
   * Tests the access method with a forgotten passed field_name.
   */
  public function testAccessWithNotPassedFieldName() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $this->createMockEntity());

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($route, $request, $account));
  }

  /**
   * Tests the access method with a non existing field.
   */
  public function testAccessWithNonExistingField() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $this->createMockEntity());
    $request->attributes->set('field_name', 'not_valid');

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($route, $request, $account));
  }

  /**
   * Tests the access method with a forgotten passed language.
   */
  public function testAccessWithNotPassedLanguage() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $this->createMockEntity());
    $request->attributes->set('field_name', 'valid');

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($route, $request, $account));
  }

  /**
   * Tests the access method with an invalid language.
   */
  public function testAccessWithInvalidLanguage() {
    $entity = $this->createMockEntity();
    $entity->expects($this->once())
      ->method('hasTranslation')
      ->with('xx-lolspeak')
      ->will($this->returnValue(FALSE));

    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $entity);
    $request->attributes->set('field_name', 'valid');
    $request->attributes->set('langcode', 'xx-lolspeak');

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($route, $request, $account));
  }

  /**
   * Returns a mock entity.
   */
  protected function createMockEntity() {
    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();

    $entity->expects($this->any())
      ->method('hasField')
      ->will($this->returnValueMap(array(
        array('valid', TRUE),
        array('not_valid', FALSE),
      )));

    return $entity;
  }

}
