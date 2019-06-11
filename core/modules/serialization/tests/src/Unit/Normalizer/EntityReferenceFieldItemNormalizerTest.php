<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Url;
use Drupal\locale\StringInterface;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer
 * @group serialization
 */
class EntityReferenceFieldItemNormalizerTest extends UnitTestCase {

  use InternalTypedDataTestTrait;

  /**
   * The mock serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $serializer;

  /**
   * The normalizer under test.
   *
   * @var \Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer
   */
  protected $normalizer;

  /**
   * The mock field item.
   *
   * @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fieldItem;

  /**
   * The mock entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityRepository;

  /**
   * The mock field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fieldDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::class);
    $this->normalizer = new EntityReferenceFieldItemNormalizer($this->entityRepository->reveal());

    $this->serializer = $this->prophesize(Serializer::class);
    // Set up the serializer to return an entity property.
    $this->serializer->normalize(Argument::cetera())
      ->willReturn('test');

    $this->normalizer->setSerializer($this->serializer->reveal());

    $this->fieldItem = $this->prophesize(EntityReferenceItem::class);
    $this->fieldItem->getIterator()
      ->willReturn(new \ArrayIterator(['target_id' => []]));

    $this->fieldDefinition = $this->prophesize(FieldDefinitionInterface::class);
    $this->fieldDefinition->getItemDefinition()
      ->willReturn($this->prophesize(FieldItemDataDefinition::class)->reveal());

  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization($this->fieldItem->reveal()));
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * @covers ::supportsDenormalization
   */
  public function testSupportsDenormalization() {
    $this->assertTrue($this->normalizer->supportsDenormalization([], EntityReferenceItem::class));
    $this->assertFalse($this->normalizer->supportsDenormalization([], FieldItemInterface::class));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $test_url = '/test/100';

    $generated_url = (new GeneratedUrl())->setGeneratedUrl($test_url);

    $url = $this->prophesize(Url::class);
    $url->toString(TRUE)
      ->willReturn($generated_url);

    $entity = $this->prophesize(EntityInterface::class);
    $entity->hasLinkTemplate('canonical')
      ->willReturn(TRUE);
    $entity->isNew()
      ->willReturn(FALSE)
      ->shouldBeCalled();
    $entity->toUrl('canonical')
      ->willReturn($url)
      ->shouldBeCalled();
    $entity->uuid()
      ->willReturn('080e3add-f9d5-41ac-9821-eea55b7b42fb')
      ->shouldBeCalled();
    $entity->getEntityTypeId()
      ->willReturn('test_type')
      ->shouldBeCalled();

    $entity_reference = $this->prophesize(TypedDataInterface::class);
    $entity_reference->getValue()
      ->willReturn($entity->reveal())
      ->shouldBeCalled();

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getSetting('target_type')
      ->willReturn('test_type');

    $this->fieldItem->getFieldDefinition()
      ->willReturn($field_definition->reveal());

    $this->fieldItem->get('entity')
      ->willReturn($entity_reference)
      ->shouldBeCalled();

    $this->fieldItem->getProperties(TRUE)
      ->willReturn(['target_id' => $this->getTypedDataProperty(FALSE)])
      ->shouldBeCalled();

    $normalized = $this->normalizer->normalize($this->fieldItem->reveal());

    $expected = [
      'target_id' => 'test',
      'target_type' => 'test_type',
      'target_uuid' => '080e3add-f9d5-41ac-9821-eea55b7b42fb',
      'url' => $test_url,
    ];
    $this->assertSame($expected, $normalized);
  }

  public function testNormalizeWithNewEntityReference() {
    $test_url = '/test/100';

    $generated_url = (new GeneratedUrl())->setGeneratedUrl($test_url);

    $url = $this->prophesize(Url::class);
    $url->toString(TRUE)
      ->willReturn($generated_url);

    $entity = $this->prophesize(EntityInterface::class);
    $entity->hasLinkTemplate('canonical')
      ->willReturn(TRUE);
    $entity->isNew()
      ->willReturn(TRUE)
      ->shouldBeCalled();
    $entity->uuid()
      ->willReturn('080e3add-f9d5-41ac-9821-eea55b7b42fb')
      ->shouldBeCalled();
    $entity->getEntityTypeId()
      ->willReturn('test_type')
      ->shouldBeCalled();
    $entity->toUrl('canonical')
      ->willReturn($url)
      ->shouldNotBeCalled();

    $entity_reference = $this->prophesize(TypedDataInterface::class);
    $entity_reference->getValue()
      ->willReturn($entity->reveal())
      ->shouldBeCalled();

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getSetting('target_type')
      ->willReturn('test_type');

    $this->fieldItem->getFieldDefinition()
      ->willReturn($field_definition->reveal());

    $this->fieldItem->get('entity')
      ->willReturn($entity_reference)
      ->shouldBeCalled();

    $this->fieldItem->getProperties(TRUE)
      ->willReturn(['target_id' => $this->getTypedDataProperty(FALSE)])
      ->shouldBeCalled();

    $normalized = $this->normalizer->normalize($this->fieldItem->reveal());

    $expected = [
      'target_id' => 'test',
      'target_type' => 'test_type',
      'target_uuid' => '080e3add-f9d5-41ac-9821-eea55b7b42fb',
    ];
    $this->assertSame($expected, $normalized);
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeWithEmptyTaxonomyTermReference() {
    // Override the serializer prophecy from setUp() to return a zero value.
    $this->serializer = $this->prophesize(Serializer::class);
    // Set up the serializer to return an entity property.
    $this->serializer->normalize(Argument::cetera())
      ->willReturn(0);

    $this->normalizer->setSerializer($this->serializer->reveal());

    $entity_reference = $this->prophesize(TypedDataInterface::class);
    $entity_reference->getValue()
      ->willReturn(NULL)
      ->shouldBeCalled();

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getSetting('target_type')
      ->willReturn('taxonomy_term');

    $this->fieldItem->getFieldDefinition()
      ->willReturn($field_definition->reveal());

    $this->fieldItem->get('entity')
      ->willReturn($entity_reference)
      ->shouldBeCalled();

    $this->fieldItem->getProperties(TRUE)
      ->willReturn(['target_id' => $this->getTypedDataProperty(FALSE)])
      ->shouldBeCalled();

    $normalized = $this->normalizer->normalize($this->fieldItem->reveal());

    $expected = [
      'target_id' => NULL,
    ];
    $this->assertSame($expected, $normalized);
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeWithNoEntity() {
    $entity_reference = $this->prophesize(TypedDataInterface::class);
    $entity_reference->getValue()
      ->willReturn(NULL)
      ->shouldBeCalled();

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getSetting('target_type')
      ->willReturn('test_type');

    $this->fieldItem->getFieldDefinition()
      ->willReturn($field_definition->reveal());

    $this->fieldItem->get('entity')
      ->willReturn($entity_reference->reveal())
      ->shouldBeCalled();

    $this->fieldItem->getProperties(TRUE)
      ->willReturn(['target_id' => $this->getTypedDataProperty(FALSE)])
      ->shouldBeCalled();

    $normalized = $this->normalizer->normalize($this->fieldItem->reveal());

    $expected = [
      'target_id' => 'test',
    ];
    $this->assertSame($expected, $normalized);
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalizeWithTypeAndUuid() {
    $data = [
      'target_id' => 'test',
      'target_type' => 'test_type',
      'target_uuid' => '080e3add-f9d5-41ac-9821-eea55b7b42fb',
    ];

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->id()
      ->willReturn('test')
      ->shouldBeCalled();
    $this->entityRepository
      ->loadEntityByUuid($data['target_type'], $data['target_uuid'])
      ->willReturn($entity)
      ->shouldBeCalled();

    $this->fieldItem->getProperties()->willReturn([
      'target_id' => $this->prophesize(IntegerInterface::class),
    ]);
    $this->fieldItem->setValue(['target_id' => 'test'])->shouldBeCalled();

    $this->assertDenormalize($data);
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalizeWithUuidWithoutType() {
    $data = [
      'target_id' => 'test',
      'target_uuid' => '080e3add-f9d5-41ac-9821-eea55b7b42fb',
    ];

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->id()
      ->willReturn('test')
      ->shouldBeCalled();
    $this->entityRepository
      ->loadEntityByUuid('test_type', $data['target_uuid'])
      ->willReturn($entity)
      ->shouldBeCalled();

    $this->fieldItem->getProperties()->willReturn([
      'target_id' => $this->prophesize(IntegerInterface::class),
    ]);
    $this->fieldItem->setValue(['target_id' => 'test'])->shouldBeCalled();

    $this->assertDenormalize($data);
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalizeWithUuidWithIncorrectType() {
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('The field "field_reference" property "target_type" must be set to "test_type" or omitted.');

    $data = [
      'target_id' => 'test',
      'target_type' => 'wrong_type',
      'target_uuid' => '080e3add-f9d5-41ac-9821-eea55b7b42fb',
    ];

    $this->fieldDefinition
      ->getName()
      ->willReturn('field_reference')
      ->shouldBeCalled();

    $this->assertDenormalize($data);
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalizeWithTypeWithIncorrectUuid() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('No "test_type" entity found with UUID "unique-but-none-non-existent" for field "field_reference"');

    $data = [
      'target_id' => 'test',
      'target_type' => 'test_type',
      'target_uuid' => 'unique-but-none-non-existent',
    ];
    $this->entityRepository
      ->loadEntityByUuid($data['target_type'], $data['target_uuid'])
      ->willReturn(NULL)
      ->shouldBeCalled();
    $this->fieldItem
      ->getName()
      ->willReturn('field_reference')
      ->shouldBeCalled();

    $this->assertDenormalize($data);
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalizeWithEmtpyUuid() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('If provided "target_uuid" cannot be empty for field "test_type".');

    $data = [
      'target_id' => 'test',
      'target_type' => 'test_type',
      'target_uuid' => '',
    ];
    $this->fieldItem
      ->getName()
      ->willReturn('field_reference')
      ->shouldBeCalled();

    $this->assertDenormalize($data);
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalizeWithId() {
    $data = [
      'target_id' => 'test',
    ];
    $this->fieldItem->setValue($data)->shouldBeCalled();

    $this->assertDenormalize($data);
  }

  /**
   * Asserts denormalization process is correct for give data.
   *
   * @param array $data
   *   The data to denormalize.
   */
  protected function assertDenormalize(array $data) {
    $this->fieldItem->getParent()
      ->willReturn($this->prophesize(FieldItemListInterface::class)->reveal());
    $this->fieldItem->getFieldDefinition()->willReturn($this->fieldDefinition->reveal());
    if (!empty($data['target_uuid'])) {
      $this->fieldDefinition
        ->getSetting('target_type')
        ->willReturn('test_type')
        ->shouldBeCalled();
    }

    // Avoid a static method call by returning dummy serialized property data.
    $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->willReturn()
      ->shouldBeCalled();
    $this->fieldDefinition
      ->getName()
      ->willReturn('field_reference')
      ->shouldBeCalled();
    $entity = $this->prophesize(EntityInterface::class);
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity->getEntityType()
      ->willReturn($entity_type->reveal())
      ->shouldBeCalled();
    $this->fieldItem
      ->getPluginDefinition()
      ->willReturn([
        'serialized_property_names' => [
          'foo' => 'bar',
        ],
      ])
      ->shouldBeCalled();
    $this->fieldItem
      ->getEntity()
      ->willReturn($entity->reveal())
      ->shouldBeCalled();

    $context = ['target_instance' => $this->fieldItem->reveal()];
    $denormalized = $this->normalizer->denormalize($data, EntityReferenceItem::class, 'json', $context);
    $this->assertSame($context['target_instance'], $denormalized);
  }

  /**
   * @covers ::constructValue
   */
  public function testConstructValueProperties() {
    $data = [
      'target_id' => 'test',
      'target_type' => 'test_type',
      'target_uuid' => '080e3add-f9d5-41ac-9821-eea55b7b42fb',
      'extra_property' => 'extra_value',
    ];

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->id()
      ->willReturn('test')
      ->shouldBeCalled();
    $this->entityRepository
      ->loadEntityByUuid($data['target_type'], $data['target_uuid'])
      ->willReturn($entity)
      ->shouldBeCalled();

    $this->fieldItem->getProperties()->willReturn([
      'target_id' => $this->prophesize(IntegerInterface::class),
      'extra_property' => $this->prophesize(StringInterface::class),
    ]);
    $this->fieldItem->setValue([
      'target_id' => 'test',
      'extra_property' => 'extra_value',
    ])->shouldBeCalled();

    $this->assertDenormalize($data);
  }

}
