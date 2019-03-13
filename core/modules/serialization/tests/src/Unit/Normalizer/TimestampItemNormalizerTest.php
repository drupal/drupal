<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\CreatedItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\serialization\Normalizer\TimestampItemNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests that entities can be serialized to supported core formats.
 *
 * @group serialization
 * @coversDefaultClass \Drupal\serialization\Normalizer\TimestampItemNormalizer
 */
class TimestampItemNormalizerTest extends UnitTestCase {

  use InternalTypedDataTestTrait;

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
  protected function setUp() {
    parent::setUp();

    $this->normalizer = new TimestampItemNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $timestamp_item = $this->createTimestampItemProphecy();
    $this->assertTrue($this->normalizer->supportsNormalization($timestamp_item->reveal()));

    $entity_ref_item = $this->prophesize(EntityReferenceItem::class);
    $this->assertFalse($this->normalizer->supportsNormalization($entity_ref_item->reveal()));
  }

  /**
   * @covers ::supportsDenormalization
   */
  public function testSupportsDenormalization() {
    $timestamp_item = $this->createTimestampItemProphecy();
    $this->assertTrue($this->normalizer->supportsDenormalization($timestamp_item->reveal(), TimestampItem::class));

    // CreatedItem extends regular TimestampItem.
    $timestamp_item = $this->prophesize(CreatedItem::class);
    $this->assertTrue($this->normalizer->supportsDenormalization($timestamp_item->reveal(), TimestampItem::class));

    $entity_ref_item = $this->prophesize(EntityReferenceItem::class);
    $this->assertFalse($this->normalizer->supportsNormalization($entity_ref_item->reveal(), TimestampItem::class));
  }

  /**
   * Tests the normalize function.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $expected = ['value' => '2016-11-06T09:02:00+00:00', 'format' => \DateTime::RFC3339];

    $timestamp_item = $this->createTimestampItemProphecy();
    $timestamp_item->getIterator()
      ->willReturn(new \ArrayIterator(['value' => 1478422920]));

    $value_property = $this->getTypedDataProperty(FALSE);
    $timestamp_item->getProperties(TRUE)
      ->willReturn(['value' => $value_property])
      ->shouldBeCalled();

    $serializer_prophecy = $this->prophesize(Serializer::class);

    $serializer_prophecy->normalize($value_property, NULL, [])
      ->willReturn(1478422920)
      ->shouldBeCalled();

    $this->normalizer->setSerializer($serializer_prophecy->reveal());

    $normalized = $this->normalizer->normalize($timestamp_item->reveal());
    $this->assertSame($expected, $normalized);
  }

  /**
   * Tests the denormalize function with good data.
   *
   * @covers ::denormalize
   * @dataProvider providerTestDenormalizeValidFormats
   */
  public function testDenormalizeValidFormats($value, $expected) {
    $normalized = ['value' => $value];

    $timestamp_item = $this->createTimestampItemProphecy();
    // The field item should be set with the expected timestamp.
    $timestamp_item->setValue(['value' => $expected])
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

    $context = ['target_instance' => $timestamp_item->reveal()];

    $denormalized = $this->normalizer->denormalize($normalized, TimestampItem::class, NULL, $context);
    $this->assertTrue($denormalized instanceof TimestampItem);
  }

  /**
   * Data provider for testDenormalizeValidFormats.
   *
   * @return array
   */
  public function providerTestDenormalizeValidFormats() {
    $expected_stamp = 1478422920;

    $data = [];

    $data['U'] = [$expected_stamp, $expected_stamp];
    $data['RFC3339'] = ['2016-11-06T09:02:00+00:00', $expected_stamp];
    $data['RFC3339 +0100'] = ['2016-11-06T09:02:00+01:00', $expected_stamp - 1 * 3600];
    $data['RFC3339 -0600'] = ['2016-11-06T09:02:00-06:00', $expected_stamp + 6 * 3600];

    $data['ISO8601'] = ['2016-11-06T09:02:00+0000', $expected_stamp];
    $data['ISO8601 +0100'] = ['2016-11-06T09:02:00+0100', $expected_stamp - 1 * 3600];
    $data['ISO8601 -0600'] = ['2016-11-06T09:02:00-0600', $expected_stamp + 6 * 3600];

    return $data;
  }

  /**
   * Tests the denormalize function with bad data.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeException() {
    $this->setExpectedException(UnexpectedValueException::class, 'The specified date "2016/11/06 09:02am GMT" is not in an accepted format: "U" (UNIX timestamp), "Y-m-d\TH:i:sO" (ISO 8601), "Y-m-d\TH:i:sP" (RFC 3339).');

    $timestamp_item = $this->createTimestampItemProphecy();

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

    $context = ['target_instance' => $timestamp_item->reveal()];

    $normalized = ['value' => '2016/11/06 09:02am GMT'];
    $this->normalizer->denormalize($normalized, TimestampItem::class, NULL, $context);
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
