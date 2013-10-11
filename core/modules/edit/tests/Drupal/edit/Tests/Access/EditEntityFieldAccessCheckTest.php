<?php

/**
 * @file
 * Contains \Drupal\edit\Tests\Access\EditEntityFieldAccessCheckTest.
 */

namespace Drupal\edit\Tests\Access {

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\edit\Access\EditEntityFieldAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\field\FieldInterface;
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
   * @var \Drupal\Core\Entity\EntityManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked field info.
   *
   * @var \Drupal\field\FieldInfo|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldInfo;

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
    $this->entityManager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityStorageController = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');

    $this->entityManager->expects($this->any())
      ->method('getStorageController')
      ->will($this->returnValue($this->entityStorageController));

    $this->fieldInfo = $this->getMockBuilder('Drupal\field\FieldInfo')
      ->disableOriginalConstructor()
      ->getMock();

    $this->editAccessCheck = new EditEntityFieldAccessCheck($this->entityManager, $this->fieldInfo);
  }

  /**
   * Tests the appliesTo method for the access checker.
   */
  public function testAppliesTo() {
    $this->assertEquals($this->editAccessCheck->appliesTo(), array('_access_edit_entity_field'), 'Access checker returned the expected appliesTo() array.');
  }

  /**
   * Provides test data for testAccess().
   *
   * @see \Drupal\edit\Tests\edit\Access\EditEntityFieldAccessCheckTest::testAccess()
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

    $field_with_access = $this->getMockBuilder('Drupal\field\Entity\Field')
      ->disableOriginalConstructor()
      ->getMock();
    $field_with_access->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $field_without_access = $this->getMockBuilder('Drupal\field\Entity\Field')
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
   * @param \Drupal\field\FieldInterface $field
   *   A mocked field.
   * @param bool|null $expected_result
   *   The expected result of the access call.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess(EntityInterface $entity, FieldInterface $field = NULL, $expected_result) {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();

    $entity_with_field = clone $entity;
    $entity_with_field->expects($this->any())
      ->method('get')
      ->will($this->returnValue($field));

    // Prepare the request to be valid.
    $request->attributes->set('entity', $entity_with_field);
    $request->attributes->set('entity_type', 'test_entity');
    $request->attributes->set('field_name', 'example');
    $request->attributes->set('langcode', Language::LANGCODE_NOT_SPECIFIED);

    $this->fieldInfo->expects($this->any())
      ->method('getInstance')
      ->will($this->returnValue(array(
        'example' => array(
          'field_name' => 'example',
        )
      )));

    $access = $this->editAccessCheck->access($route, $request);
    $this->assertSame($expected_result, $access);
  }

  /**
   * Tests the access method with an undefined entity type.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testAccessWithUndefinedEntityType() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'non_valid');

    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->with('non_valid')
      ->will($this->returnValue(NULL));

    $this->editAccessCheck->access($route, $request);
  }

  /**
   * Tests the access method with a non existing entity.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
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

    $this->editAccessCheck->access($route, $request);
  }

  /**
   * Tests the access method with a forgotten passed field_name.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testAccessWithNotPassedFieldName() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');

    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();

    $request->attributes->set('entity', $entity);

    $this->editAccessCheck->access($route, $request);
  }

  /**
   * Tests the access method with a non existing field.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testAccessWithNonExistingField() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');

    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->any())
      ->method('entityType')
      ->will($this->returnValue('entity_test'));
    $entity->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_bundle'));

    $request->attributes->set('entity', $entity);
    $request->attributes->set('field_name', 'not_valid');

    $this->fieldInfo->expects($this->once())
      ->method('getInstance')
      ->with('entity_test', 'test_bundle', 'not_valid')
      ->will($this->returnValue(NULL));

    $this->editAccessCheck->access($route, $request);
  }

  /**
   * Tests the access method with a forgotten passed language.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testAccessWithNotPassedLanguage() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');

    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();
    $request->attributes->set('entity', $entity);

    $request->attributes->set('field_name', 'valid');

    $field = $this->getMockBuilder('Drupal\field\Entity\Field')
      ->disableOriginalConstructor()
      ->getMock();

    $this->fieldInfo->expects($this->once())
      ->method('getInstance')
      ->will($this->returnValue($field));

    $this->editAccessCheck->access($route, $request);
  }

  /**
   * Tests the access method with an invalid language.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testAccessWithInvalidLanguage() {
    $route = new Route('/edit/form/test_entity/1/body/und/full', array(), array('_access_edit_entity_field' => 'TRUE'));
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');

    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();
    $request->attributes->set('entity', $entity);

    $request->attributes->set('field_name', 'valid');
    $request->attributes->set('langcode', 'xx-lolspeak');

    $field = $this->getMockBuilder('Drupal\field\Entity\Field')
      ->disableOriginalConstructor()
      ->getMock();

    $this->fieldInfo->expects($this->once())
      ->method('getInstance')
      ->will($this->returnValue($field));

    $this->editAccessCheck->access($route, $request);
  }

}

}

// @todo remove once field_access() and field_valid_language() can be injected.
namespace {

  use Drupal\Core\Language\Language;

  if (!function_exists('field_valid_language')) {
    function field_valid_language($langcode, $default = TRUE) {
      return $langcode == Language::LANGCODE_NOT_SPECIFIED ? Language::LANGCODE_NOT_SPECIFIED : 'en';
    }
  }

}
