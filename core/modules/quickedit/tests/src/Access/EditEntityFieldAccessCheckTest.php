<?php

/**
 * @file
 * Contains \Drupal\quickedit\Tests\Access\EditEntityFieldAccessCheckTest.
 */

namespace Drupal\quickedit\Tests\Access;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\quickedit\Access\EditEntityFieldAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * @coversDefaultClass \Drupal\quickedit\Access\EditEntityFieldAccessCheck
 * @group quickedit
 */
class EditEntityFieldAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\quickedit\Access\EditEntityFieldAccessCheck
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

    $this->editAccessCheck = new EditEntityFieldAccessCheck($this->entityManager);
  }

  /**
   * Provides test data for testAccess().
   *
   * @see \Drupal\edit\Tests\quickedit\Access\EditEntityFieldAccessCheckTest::testAccess()
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

    $field_storage_with_access = $this->getMockBuilder('Drupal\field\Entity\FieldStorageConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage_with_access->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $field_storage_without_access = $this->getMockBuilder('Drupal\field\Entity\FieldStorageConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage_without_access->expects($this->any())
      ->method('access')
      ->will($this->returnValue(FALSE));

    $data = array();
    $data[] = array($editable_entity, $field_storage_with_access, AccessCheckInterface::ALLOW);
    $data[] = array($non_editable_entity, $field_storage_with_access, AccessCheckInterface::DENY);
    $data[] = array($editable_entity, $field_storage_without_access, AccessCheckInterface::DENY);
    $data[] = array($non_editable_entity, $field_storage_without_access, AccessCheckInterface::DENY);

    return $data;
  }

  /**
   * Tests the method for checking access to routes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A mocked entity.
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   A mocked field storage.
   * @param bool|null $expected_result
   *   The expected result of the access call.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess(EntityInterface $entity, FieldStorageConfigInterface $field_storage = NULL, $expected_result) {
    $request = new Request();

    $field_name = 'valid';
    $entity_with_field = clone $entity;
    $entity_with_field->expects($this->any())
      ->method('get')
      ->with($field_name)
      ->will($this->returnValue($field_storage));
    $entity_with_field->expects($this->once())
      ->method('hasTranslation')
      ->with(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->will($this->returnValue(TRUE));

    // Prepare the request to be valid.
    $request->attributes->set('entity_type', 'test_entity');
    $request->attributes->set('entity', $entity_with_field);
    $request->attributes->set('field_name', $field_name);
    $request->attributes->set('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $access = $this->editAccessCheck->access($request, $field_name, $account);
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
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, NULL, $account));
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
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, NULL, $account));
  }

  /**
   * Tests the access method with a forgotten passed field_name.
   */
  public function testAccessWithNotPassedFieldName() {
    $request = new Request();
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $this->createMockEntity());

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, NULL, $account));
  }

  /**
   * Tests the access method with a non existing field.
   */
  public function testAccessWithNonExistingField() {
    $request = new Request();
    $field_name = 'not_valid';
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $this->createMockEntity());
    $request->attributes->set('field_name', $field_name);

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, $field_name, $account));
  }

  /**
   * Tests the access method with a forgotten passed language.
   */
  public function testAccessWithNotPassedLanguage() {
    $request = new Request();
    $field_name = 'valid';
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $this->createMockEntity());
    $request->attributes->set('field_name', $field_name);

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, $field_name, $account));
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

    $request = new Request();
    $field_name = 'valid';
    $request->attributes->set('entity_type', 'entity_test');
    $request->attributes->set('entity', $entity);
    $request->attributes->set('field_name', $field_name);
    $request->attributes->set('langcode', 'xx-lolspeak');

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertSame(AccessCheckInterface::KILL, $this->editAccessCheck->access($request, $field_name, $account));
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
