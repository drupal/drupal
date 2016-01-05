<?php

/**
 * @file
 * Contains \Drupal\Tests\serialization\Unit\Normalizer\EntityReferenceFieldItemNormalizerTest.
 */

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer
 * @group serialization
 */
class EntityReferenceFieldItemNormalizerTest extends UnitTestCase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new EntityReferenceFieldItemNormalizer();

    $this->serializer = $this->prophesize(Serializer::class);
    // Set up the serializer to return an entity property.
    $this->serializer->normalize(Argument::cetera())
      ->willReturn(['value' => 'test']);

    $this->normalizer->setSerializer($this->serializer->reveal());

    $this->fieldItem = $this->prophesize(EntityReferenceItem::class);
    $this->fieldItem->getIterator()
      ->willReturn(new \ArrayIterator(['target_id' => []]));
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization($this->fieldItem->reveal()));
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $test_url = '/test/100';

    $entity = $this->prophesize(EntityInterface::class);
    $entity->url('canonical')
      ->willReturn($test_url)
      ->shouldBeCalled();

    $entity_reference = $this->prophesize(TypedDataInterface::class);
    $entity_reference->getValue()
      ->willReturn($entity->reveal())
      ->shouldBeCalled();

    $this->fieldItem->get('entity')
      ->willReturn($entity_reference)
      ->shouldBeCalled();

    $normalized = $this->normalizer->normalize($this->fieldItem->reveal());

    $expected = [
      'target_id' => ['value' => 'test'],
      'url' => $test_url,
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

    $this->fieldItem->get('entity')
      ->willReturn($entity_reference->reveal())
      ->shouldBeCalled();

    $normalized = $this->normalizer->normalize($this->fieldItem->reveal());

    $expected = [
      'target_id' => ['value' => 'test'],
    ];
    $this->assertSame($expected, $normalized);
  }

}
