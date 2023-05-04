<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\ContentEntityNormalizer
 * @group serialization
 */
class ContentEntityNormalizerTest extends UnitTestCase {

  /**
   * The mock serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $serializer;

  /**
   * The normalizer under test.
   *
   * @var \Drupal\serialization\Normalizer\ContentEntityNormalizer
   */
  protected $contentEntityNormalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);

    $this->contentEntityNormalizer = new ContentEntityNormalizer($entity_type_manager, $entity_type_repository, $entity_field_manager);

    $this->serializer = $this->prophesize(Serializer::class);
    $this->contentEntityNormalizer->setSerializer($this->serializer->reveal());
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $content_mock = $this->createMock('Drupal\Core\Entity\ContentEntityInterface');
    $config_mock = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $this->assertTrue($this->contentEntityNormalizer->supportsNormalization($content_mock));
    $this->assertFalse($this->contentEntityNormalizer->supportsNormalization($config_mock));
  }

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $this->serializer->normalize(Argument::type(FieldItemListInterface::class),
      'test_format', ['account' => NULL])->willReturn('test');

    $definitions = [
      'field_accessible_external' => $this->createMockFieldListItem(TRUE, FALSE),
      'field_non-accessible_external' => $this->createMockFieldListItem(FALSE, FALSE),
      'field_accessible_internal' => $this->createMockFieldListItem(TRUE, TRUE),
      'field_non-accessible_internal' => $this->createMockFieldListItem(FALSE, TRUE),
    ];
    $content_entity_mock = $this->createMockForContentEntity($definitions);

    $normalized = $this->contentEntityNormalizer->normalize($content_entity_mock, 'test_format');

    $this->assertArrayHasKey('field_accessible_external', $normalized);
    $this->assertEquals('test', $normalized['field_accessible_external']);
    $this->assertArrayNotHasKey('field_non-accessible_external', $normalized);
    $this->assertArrayNotHasKey('field_accessible_internal', $normalized);
    $this->assertArrayNotHasKey('field_non-accessible_internal', $normalized);
  }

  /**
   * Tests the normalize() method with account context passed.
   *
   * @covers ::normalize
   */
  public function testNormalizeWithAccountContext() {
    $mock_account = $this->createMock('Drupal\Core\Session\AccountInterface');

    $context = [
      'account' => $mock_account,
    ];

    $this->serializer->normalize(Argument::type(FieldItemListInterface::class),
      'test_format', $context)->willReturn('test');

    // The mock account should get passed directly into the access() method on
    // field items from $context['account'].
    $definitions = [
      'field_1' => $this->createMockFieldListItem(TRUE, FALSE, $mock_account),
      'field_2' => $this->createMockFieldListItem(FALSE, FALSE, $mock_account),
    ];
    $content_entity_mock = $this->createMockForContentEntity($definitions);

    $normalized = $this->contentEntityNormalizer->normalize($content_entity_mock, 'test_format', $context);

    $this->assertArrayHasKey('field_1', $normalized);
    $this->assertEquals('test', $normalized['field_1']);
    $this->assertArrayNotHasKey('field_2', $normalized);
  }

  /**
   * Creates a mock content entity.
   *
   * @param $definitions
   *   The properties the will be returned.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   */
  public function createMockForContentEntity($definitions) {
    $content_entity_mock = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->onlyMethods(['getTypedData'])
      ->getMockForAbstractClass();
    $typed_data = $this->prophesize(ComplexDataInterface::class);
    $typed_data->getProperties(TRUE)
      ->willReturn($definitions)
      ->shouldBeCalled();
    $content_entity_mock->expects($this->any())
      ->method('getTypedData')
      ->willReturn($typed_data->reveal());

    return $content_entity_mock;
  }

  /**
   * Creates a mock field list item.
   *
   * @param bool $access
   *   The value that access() will return.
   * @param bool $internal
   *   The value that isInternal() will return.
   * @param \Drupal\Core\Session\AccountInterface $user_context
   *   The user context used for the access check.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function createMockFieldListItem($access, $internal, AccountInterface $user_context = NULL) {
    $data_definition = $this->prophesize(DataDefinitionInterface::class);
    $mock = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $mock->expects($this->once())
      ->method('getDataDefinition')
      ->willReturn($data_definition->reveal());
    $data_definition->isInternal()
      ->willReturn($internal)
      ->shouldBeCalled();
    if (!$internal) {
      $mock->expects($this->once())
        ->method('access')
        ->with('view', $user_context)
        ->willReturn($access);
    }
    return $mock;
  }

}
