<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\CreatedItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;
use Drupal\serialization\Normalizer\TimestampItemNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests that TimestampItem (de)normalization uses Timestamp (de)normalization.
 *
 * @group serialization
 * @coversDefaultClass \Drupal\serialization\Normalizer\TimestampItemNormalizer
 * @see \Drupal\serialization\Normalizer\TimestampNormalizer
 */
class TimestampItemNormalizerTest extends UnitTestCase {

  /**
   * @var \Drupal\serialization\Normalizer\TimestampItemNormalizer
   */
  protected $normalizer;

  /**
   * The test TimestampItem.
   *
   * @var \Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem
   */
  protected $item;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->normalizer = new TimestampItemNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization(): void {
    $timestamp_item = $this->createTimestampItemProphecy();
    $this->assertTrue($this->normalizer->supportsNormalization($timestamp_item->reveal()));

    $entity_ref_item = $this->prophesize(EntityReferenceItem::class);
    $this->assertFalse($this->normalizer->supportsNormalization($entity_ref_item->reveal()));
  }

  /**
   * @covers ::supportsDenormalization
   */
  public function testSupportsDenormalization(): void {
    $timestamp_item = $this->createTimestampItemProphecy();
    $this->assertTrue($this->normalizer->supportsDenormalization($timestamp_item->reveal(), TimestampItem::class));

    // CreatedItem extends regular TimestampItem.
    $timestamp_item = $this->prophesize(CreatedItem::class);
    $this->assertTrue($this->normalizer->supportsDenormalization($timestamp_item->reveal(), TimestampItem::class));

    $entity_ref_item = $this->prophesize(EntityReferenceItem::class);
    $this->assertFalse($this->normalizer->supportsNormalization($entity_ref_item->reveal(), TimestampItem::class));
  }

  /**
   * @covers ::normalize
   * @see \Drupal\Tests\serialization\Unit\Normalizer\TimestampNormalizerTest
   */
  public function testNormalize(): void {
    // Mock TimestampItem @FieldType, which contains a Timestamp @DataType,
    // which has a DataDefinition.
    $data_definition = $this->prophesize(DataDefinitionInterface::class);
    $data_definition->isInternal()
      ->willReturn(FALSE)
      ->shouldBeCalled();
    $timestamp = $this->prophesize(Timestamp::class);
    $timestamp->getDataDefinition()
      ->willReturn($data_definition->reveal())
      ->shouldBeCalled();
    $timestamp = $timestamp->reveal();
    $timestamp_item = $this->createTimestampItemProphecy();
    $timestamp_item->getProperties(TRUE)
      ->willReturn(['value' => $timestamp])
      ->shouldBeCalled();

    // Mock Serializer service, to assert that the Timestamp @DataType
    // normalizer would be called.
    $timestamp_datetype_normalization = $this->randomMachineName();
    $serializer_prophecy = $this->prophesize(Serializer::class);
    // This is where \Drupal\serialization\Normalizer\TimestampNormalizer would
    // be called.
    $serializer_prophecy->normalize($timestamp, NULL, [])
      ->willReturn($timestamp_datetype_normalization)
      ->shouldBeCalled();

    $this->normalizer->setSerializer($serializer_prophecy->reveal());

    $normalized = $this->normalizer->normalize($timestamp_item->reveal());
    $this->assertSame(['value' => $timestamp_datetype_normalization, 'format' => \DateTime::RFC3339], $normalized);
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalize(): void {
    $timestamp_item_normalization = [
      'value' => $this->randomMachineName(),
      'format' => \DateTime::RFC3339,
    ];
    $timestamp_data_denormalization = $this->randomMachineName();

    $timestamp_item = $this->createTimestampItemProphecy();
    // The field item should get the Timestamp @DataType denormalization set as
    // a value, in FieldItemNormalizer::denormalize().
    $timestamp_item->setValue(['value' => $timestamp_data_denormalization])
      ->shouldBeCalled();

    // Avoid a static method call by returning dummy serialized property data.
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $timestamp_item
      ->getFieldDefinition()
      ->willReturn($field_definition->reveal())
      ->shouldBeCalled();
    $timestamp_item->getPluginDefinition()
      ->willReturn([
        'serialized_property_names' => [
          'foo' => 'bar',
        ],
      ])
      ->shouldBeCalled();
    $entity = $this->prophesize(EntityInterface::class);
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity->getEntityType()
      ->willReturn($entity_type->reveal())
      ->shouldBeCalled();
    $timestamp_item
      ->getEntity()
      ->willReturn($entity->reveal())
      ->shouldBeCalled();

    $context = [
      'target_instance' => $timestamp_item->reveal(),
      'datetime_allowed_formats' => [\DateTime::RFC3339],
    ];

    // Mock Serializer service, to assert that the Timestamp @DataType
    // denormalizer would be called.
    $serializer_prophecy = $this->prophesize(Serializer::class);
    // This is where \Drupal\serialization\Normalizer\TimestampNormalizer would
    // be called.
    $serializer_prophecy->denormalize($timestamp_item_normalization['value'], Timestamp::class, NULL, $context)
      ->willReturn($timestamp_data_denormalization)
      ->shouldBeCalled();

    $this->normalizer->setSerializer($serializer_prophecy->reveal());

    $denormalized = $this->normalizer->denormalize($timestamp_item_normalization, TimestampItem::class, NULL, $context);
    $this->assertInstanceOf(TimestampItem::class, $denormalized);
  }

  /**
   * Creates a TimestampItem prophecy.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem
   */
  protected function createTimestampItemProphecy() {
    $timestamp_item = $this->prophesize(TimestampItem::class);
    $timestamp_item->getParent()
      ->willReturn(TRUE);

    return $timestamp_item;
  }

}
