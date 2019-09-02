<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\serialization\Normalizer\EntityNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\EntityNormalizer
 * @group serialization
 */
class EntityNormalizerTest extends UnitTestCase {

  /**
   * The mock entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mock entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeRepository;

  /**
   * The mock serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $serializer;

  /**
   * The entity normalizer.
   *
   * @var \Drupal\serialization\Normalizer\EntityNormalizer
   */
  protected $entityNormalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeRepository = $this->createMock(EntityTypeRepositoryInterface::class);

    $this->entityNormalizer = new EntityNormalizer(
      $this->entityTypeManager,
      $this->entityTypeRepository,
      $this->entityFieldManager
    );
  }

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $list_item_1 = $this->createMock('Drupal\Core\TypedData\TypedDataInterface');
    $list_item_2 = $this->createMock('Drupal\Core\TypedData\TypedDataInterface');

    $definitions = [
      'field_1' => $list_item_1,
      'field_2' => $list_item_2,
    ];

    $content_entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->setMethods(['getFields'])
      ->getMockForAbstractClass();
    $content_entity->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue($definitions));

    $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
      ->disableOriginalConstructor()
      ->setMethods(['normalize'])
      ->getMock();
    $serializer->expects($this->at(0))
      ->method('normalize')
      ->with($list_item_1, 'test_format');
    $serializer->expects($this->at(1))
      ->method('normalize')
      ->with($list_item_2, 'test_format');

    $this->entityNormalizer->setSerializer($serializer);

    $this->entityNormalizer->normalize($content_entity, 'test_format');
  }

  /**
   * Tests the denormalize() method with no entity type provided in context.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithNoEntityType() {
    $this->expectException(UnexpectedValueException::class);
    $this->entityNormalizer->denormalize([], 'Drupal\Core\Entity\ContentEntityBase');
  }

  /**
   * Tests the denormalize method with a bundle property.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithValidBundle() {
    $test_data = [
      'key_1' => 'value_1',
      'key_2' => 'value_2',
      'test_type' => [
        ['name' => 'test_bundle'],
      ],
    ];

    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');

    $entity_type->expects($this->once())
      ->method('id')
      ->willReturn('test');
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue(TRUE));
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->will($this->returnValue('test_type'));
    $entity_type->expects($this->once())
      ->method('entityClassImplements')
      ->with(FieldableEntityInterface::class)
      ->willReturn(TRUE);

    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->will($this->returnValue('test_bundle'));

    $entity_type_storage_definition = $this->createMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $entity_type_storage_definition->expects($this->once())
      ->method('getMainPropertyName')
      ->will($this->returnValue('name'));

    $entity_type_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $entity_type_definition->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->will($this->returnValue($entity_type_storage_definition));

    $base_definitions = [
      'test_type' => $entity_type_definition,
    ];

    $this->entityTypeManager->expects($this->at(0))
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));
    $this->entityFieldManager->expects($this->at(0))
      ->method('getBaseFieldDefinitions')
      ->with('test')
      ->will($this->returnValue($base_definitions));

    $entity_query_mock = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
    $entity_query_mock->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test_bundle' => 'test_bundle']));

    $entity_type_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_type_storage->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($entity_query_mock));

    $this->entityTypeManager->expects($this->at(1))
      ->method('getStorage')
      ->with('test_bundle')
      ->will($this->returnValue($entity_type_storage));

    $key_1 = $this->createMock(FieldItemListInterface::class);
    $key_2 = $this->createMock(FieldItemListInterface::class);

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->expects($this->at(0))
      ->method('get')
      ->with('key_1')
      ->willReturn($key_1);
    $entity->expects($this->at(1))
      ->method('get')
      ->with('key_2')
      ->willReturn($key_2);

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // Create should only be called with the bundle property at first.
    $expected_test_data = [
      'test_type' => 'test_bundle',
    ];

    $storage->expects($this->once())
      ->method('create')
      ->with($expected_test_data)
      ->will($this->returnValue($entity));

    $this->entityTypeManager->expects($this->at(2))
      ->method('getStorage')
      ->with('test')
      ->will($this->returnValue($storage));

    // Setup expectations for the serializer. This will be called for each field
    // item.
    $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
      ->disableOriginalConstructor()
      ->setMethods(['denormalize'])
      ->getMock();
    $serializer->expects($this->at(0))
      ->method('denormalize')
      ->with('value_1', get_class($key_1), NULL, ['target_instance' => $key_1, 'entity_type' => 'test']);
    $serializer->expects($this->at(1))
      ->method('denormalize')
      ->with('value_2', get_class($key_2), NULL, ['target_instance' => $key_2, 'entity_type' => 'test']);

    $this->entityNormalizer->setSerializer($serializer);

    $this->assertNotNull($this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, ['entity_type' => 'test']));
  }

  /**
   * Tests the denormalize method with a bundle property.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithInvalidBundle() {
    $test_data = [
      'key_1' => 'value_1',
      'key_2' => 'value_2',
      'test_type' => [
        ['name' => 'test_bundle'],
      ],
    ];

    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');

    $entity_type->expects($this->once())
      ->method('id')
      ->willReturn('test');
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue(TRUE));
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->will($this->returnValue('test_type'));
    $entity_type->expects($this->once())
      ->method('entityClassImplements')
      ->with(FieldableEntityInterface::class)
      ->willReturn(TRUE);

    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->will($this->returnValue('test_bundle'));

    $entity_type_storage_definition = $this->createMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $entity_type_storage_definition->expects($this->once())
      ->method('getMainPropertyName')
      ->will($this->returnValue('name'));

    $entity_type_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $entity_type_definition->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->will($this->returnValue($entity_type_storage_definition));

    $base_definitions = [
      'test_type' => $entity_type_definition,
    ];

    $this->entityTypeManager->expects($this->at(0))
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));
    $this->entityFieldManager->expects($this->at(0))
      ->method('getBaseFieldDefinitions')
      ->with('test')
      ->will($this->returnValue($base_definitions));

    $entity_query_mock = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
    $entity_query_mock->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test_bundle_other' => 'test_bundle_other']));

    $entity_type_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_type_storage->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($entity_query_mock));

    $this->entityTypeManager->expects($this->at(1))
      ->method('getStorage')
      ->with('test_bundle')
      ->will($this->returnValue($entity_type_storage));

    $this->expectException(UnexpectedValueException::class);
    $this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, ['entity_type' => 'test']);
  }

  /**
   * Tests the denormalize method with no bundle defined.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithNoBundle() {
    $test_data = [
      'key_1' => 'value_1',
      'key_2' => 'value_2',
    ];

    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('entityClassImplements')
      ->with(FieldableEntityInterface::class)
      ->willReturn(TRUE);
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue(FALSE));
    $entity_type->expects($this->never())
      ->method('getKey');

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));

    $key_1 = $this->createMock(FieldItemListInterface::class);
    $key_2 = $this->createMock(FieldItemListInterface::class);

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->expects($this->at(0))
      ->method('get')
      ->with('key_1')
      ->willReturn($key_1);
    $entity->expects($this->at(1))
      ->method('get')
      ->with('key_2')
      ->willReturn($key_2);

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('create')
      ->with([])
      ->will($this->returnValue($entity));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('test')
      ->will($this->returnValue($storage));

    $this->entityFieldManager->expects($this->never())
      ->method('getBaseFieldDefinitions');

    // Setup expectations for the serializer. This will be called for each field
    // item.
    $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
      ->disableOriginalConstructor()
      ->setMethods(['denormalize'])
      ->getMock();
    $serializer->expects($this->at(0))
      ->method('denormalize')
      ->with('value_1', get_class($key_1), NULL, ['target_instance' => $key_1, 'entity_type' => 'test']);
    $serializer->expects($this->at(1))
      ->method('denormalize')
      ->with('value_2', get_class($key_2), NULL, ['target_instance' => $key_2, 'entity_type' => 'test']);

    $this->entityNormalizer->setSerializer($serializer);

    $this->assertNotNull($this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, ['entity_type' => 'test']));
  }

  /**
   * Tests the denormalize method with no bundle defined.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithNoFieldableEntityType() {
    $test_data = [
      'key_1' => 'value_1',
      'key_2' => 'value_2',
    ];

    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('entityClassImplements')
      ->with(FieldableEntityInterface::class)
      ->willReturn(FALSE);

    $entity_type->expects($this->never())
      ->method('getKey');

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('create')
      ->with($test_data)
      ->will($this->returnValue($this->createMock('Drupal\Core\Entity\EntityInterface')));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('test')
      ->will($this->returnValue($storage));

    $this->entityFieldManager->expects($this->never())
      ->method('getBaseFieldDefinitions');

    $this->assertNotNull($this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, ['entity_type' => 'test']));
  }

}
