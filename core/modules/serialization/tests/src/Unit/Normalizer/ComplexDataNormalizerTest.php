<?php

/**
 * @file
 * Contains \Drupal\Tests\serialization\Unit\Normalizer\ComplexDataNormalizerTest.
 */

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\ComplexDataNormalizer
 * @group serialization
 */
class ComplexDataNormalizerTest extends UnitTestCase {

  use InternalTypedDataTestTrait;

  /**
   * Test format string.
   *
   * @var string
   */
  const TEST_FORMAT = 'test_format';

  /**
   * The Complex data normalizer under test.
   *
   * @var \Drupal\serialization\Normalizer\ComplexDataNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->normalizer = new ComplexDataNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $complex_data = $this->prophesize(ComplexDataInterface::class)->reveal();
    $this->assertTrue($this->normalizer->supportsNormalization($complex_data));
    // Also test that an object not implementing ComplexDataInterface fails.
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * Test normalizing complex data.
   *
   * @covers ::normalize
   */
  public function testNormalizeComplexData() {
    $serializer_prophecy = $this->prophesize(Serializer::class);

    $non_internal_property = $this->getTypedDataProperty(FALSE);

    $serializer_prophecy->normalize($non_internal_property, static::TEST_FORMAT, [])
      ->willReturn('A-normalized')
      ->shouldBeCalled();

    $this->normalizer->setSerializer($serializer_prophecy->reveal());

    $complex_data = $this->prophesize(ComplexDataInterface::class);
    $complex_data->getProperties(TRUE)
      ->willReturn([
        'prop:a' => $non_internal_property,
        'prop:internal' => $this->getTypedDataProperty(TRUE),
      ])
      ->shouldBeCalled();

    $normalized = $this->normalizer->normalize($complex_data->reveal(), static::TEST_FORMAT);
    $this->assertEquals(['prop:a' => 'A-normalized'], $normalized);
  }

  /**
   * Test normalize() where $object does not implement ComplexDataInterface.
   *
   * Normalizers extending ComplexDataNormalizer may have a different supported
   * class.
   *
   * @covers ::normalize
   */
  public function testNormalizeNonComplex() {
    $normalizer = new TestExtendedNormalizer();
    $serialization_context = ['test' => 'test'];

    $serializer_prophecy = $this->prophesize(Serializer::class);
    $serializer_prophecy->normalize('A', static::TEST_FORMAT, $serialization_context)
      ->willReturn('A-normalized')
      ->shouldBeCalled();
    $serializer_prophecy->normalize('B', static::TEST_FORMAT, $serialization_context)
      ->willReturn('B-normalized')
      ->shouldBeCalled();

    $normalizer->setSerializer($serializer_prophecy->reveal());

    $stdClass = new \stdClass();
    $stdClass->a = 'A';
    $stdClass->b = 'B';

    $normalized = $normalizer->normalize($stdClass, static::TEST_FORMAT, $serialization_context);
    $this->assertEquals(['a' => 'A-normalized', 'b' => 'B-normalized'], $normalized);

  }

}

/**
 * Test normalizer with a different supported class.
 */
class TestExtendedNormalizer extends ComplexDataNormalizer {
  protected $supportedInterfaceOrClass = \stdClass::class;

}
