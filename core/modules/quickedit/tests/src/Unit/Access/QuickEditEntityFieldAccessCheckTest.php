<?php

namespace Drupal\Tests\quickedit\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\quickedit\Access\QuickEditEntityFieldAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Language\LanguageInterface;

/**
 * @coversDefaultClass \Drupal\quickedit\Access\QuickEditEntityFieldAccessCheck
 * @group Access
 * @group quickedit
 */
class QuickEditEntityFieldAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\quickedit\Access\QuickEditEntityFieldAccessCheck
   */
  protected $editAccessCheck;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->editAccessCheck = new QuickEditEntityFieldAccessCheck();

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Provides test data for testAccess().
   *
   * @see \Drupal\Tests\edit\Unit\quickedit\Access\QuickEditEntityFieldAccessCheckTest::testAccess()
   */
  public function providerTestAccess() {
    $data = [];
    $data[] = [TRUE, TRUE, AccessResult::allowed()];
    $data[] = [FALSE, TRUE, AccessResult::neutral()];
    $data[] = [TRUE, FALSE, AccessResult::neutral()];
    $data[] = [FALSE, FALSE, AccessResult::neutral()];

    return $data;
  }

  /**
   * Tests the method for checking access to routes.
   *
   * @param bool $entity_is_editable
   *   Whether the subject entity is editable.
   * @param bool $field_storage_is_accessible
   *   Whether the user has access to the field storage entity.
   * @param \Drupal\Core\Access\AccessResult $expected_result
   *   The expected result of the access call.
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess($entity_is_editable, $field_storage_is_accessible, AccessResult $expected_result) {
    $entity = $this->createMockEntity();
    $entity->expects($this->any())
      ->method('access')
      ->willReturn(AccessResult::allowedIf($entity_is_editable)->cachePerPermissions());

    $field_storage = $this->createMock('Drupal\field\FieldStorageConfigInterface');
    $field_storage->expects($this->any())
      ->method('access')
      ->willReturn(AccessResult::allowedIf($field_storage_is_accessible));

    $expected_result->cachePerPermissions();

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

    $account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $access = $this->editAccessCheck->access($entity_with_field, $field_name, LanguageInterface::LANGCODE_NOT_SPECIFIED, $account);
    $this->assertEquals($expected_result, $access);
  }

  /**
   * Tests checking access to routes that result in AccessResult::isForbidden().
   *
   * @dataProvider providerTestAccessForbidden
   */
  public function testAccessForbidden($field_name, $langcode) {
    $account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $entity = $this->createMockEntity();
    $this->assertEquals(AccessResult::forbidden(), $this->editAccessCheck->access($entity, $field_name, $langcode, $account));
  }

  /**
   * Provides test data for testAccessForbidden.
   */
  public function providerTestAccessForbidden() {
    $data = [];
    // Tests the access method without a field_name.
    $data[] = [NULL, LanguageInterface::LANGCODE_NOT_SPECIFIED];
    // Tests the access method with a non-existent field.
    $data[] = ['not_valid', LanguageInterface::LANGCODE_NOT_SPECIFIED];
    // Tests the access method without a langcode.
    $data[] = ['valid', NULL];
    // Tests the access method with an invalid langcode.
    $data[] = ['valid', 'xx-lolspeak'];
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
      ->will($this->returnValueMap([
        [LanguageInterface::LANGCODE_NOT_SPECIFIED, TRUE],
        ['xx-lolspeak', FALSE],
      ]));
    $entity->expects($this->any())
      ->method('hasField')
      ->will($this->returnValueMap([
        ['valid', TRUE],
        ['not_valid', FALSE],
      ]));

    return $entity;
  }

}
