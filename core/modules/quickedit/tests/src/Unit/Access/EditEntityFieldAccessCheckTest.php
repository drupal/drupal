<?php

/**
 * @file
 * Contains \Drupal\Tests\quickedit\Unit\Access\EditEntityFieldAccessCheckTest.
 */

namespace Drupal\Tests\quickedit\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\quickedit\Access\EditEntityFieldAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * @coversDefaultClass \Drupal\quickedit\Access\EditEntityFieldAccessCheck
 * @group Access
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
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->editAccessCheck = new EditEntityFieldAccessCheck();
  }

  /**
   * Provides test data for testAccess().
   *
   * @see \Drupal\Tests\edit\Unit\quickedit\Access\EditEntityFieldAccessCheckTest::testAccess()
   */
  public function providerTestAccess() {
    $editable_entity = $this->createMockEntity();
    $editable_entity->expects($this->any())
      ->method('access')
      ->will($this->returnValue(AccessResult::allowed()->cachePerRole()));

    $non_editable_entity = $this->createMockEntity();
    $non_editable_entity->expects($this->any())
      ->method('access')
      ->will($this->returnValue(AccessResult::neutral()->cachePerRole()));

    $field_storage_with_access = $this->getMockBuilder('Drupal\field\Entity\FieldStorageConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage_with_access->expects($this->any())
      ->method('access')
      ->will($this->returnValue(AccessResult::allowed()));
    $field_storage_without_access = $this->getMockBuilder('Drupal\field\Entity\FieldStorageConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage_without_access->expects($this->any())
      ->method('access')
      ->will($this->returnValue(AccessResult::neutral()));

    $data = array();
    $data[] = array($editable_entity, $field_storage_with_access, AccessResult::allowed()->cachePerRole());
    $data[] = array($non_editable_entity, $field_storage_with_access, AccessResult::neutral()->cachePerRole());
    $data[] = array($editable_entity, $field_storage_without_access, AccessResult::neutral()->cachePerRole());
    $data[] = array($non_editable_entity, $field_storage_without_access, AccessResult::neutral()->cachePerRole());

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

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $access = $this->editAccessCheck->access($entity_with_field, $field_name, LanguageInterface::LANGCODE_NOT_SPECIFIED, $account);
    $this->assertEquals($expected_result, $access);
  }

  /**
   * Tests checking access to routes that result in AccessResult::isForbidden().
   *
   * @dataProvider providerTestAccessForbidden
   */
  public function testAccessForbidden($field_name, $langcode) {
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $entity = $this->createMockEntity();
    $this->assertEquals(AccessResult::forbidden(), $this->editAccessCheck->access($entity, $field_name, $langcode, $account));
  }

  /**
   * Provides test data for testAccessForbidden.
   */
  public function providerTestAccessForbidden() {
    $data = array();
    // Tests the access method without a field_name.
    $data[] = array(NULL, LanguageInterface::LANGCODE_NOT_SPECIFIED);
    // Tests the access method with a non-existent field.
    $data[] = array('not_valid', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    // Tests the access method without a langcode.
    $data[] = array('valid', NULL);
    // Tests the access method with an invalid langcode.
    $data[] = array('valid', 'xx-lolspeak');
    return $data;
  }

  /**
   * Returns a mock entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function createMockEntity() {
    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();

    $entity->expects($this->any())
      ->method('hasTranslation')
      ->will($this->returnValueMap(array(
        array(LanguageInterface::LANGCODE_NOT_SPECIFIED, TRUE),
        array('xx-lolspeak', FALSE),
      )));
    $entity->expects($this->any())
      ->method('hasField')
      ->will($this->returnValueMap(array(
        array('valid', TRUE),
        array('not_valid', FALSE),
      )));

    return $entity;
  }

}
