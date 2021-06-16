<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Tests\UnitTestCase;
use Drupal\serialization\Normalizer\PrimitiveDataNormalizer;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\PrimitiveDataNormalizer
 * @group serialization
 */
class PrimitiveDataNormalizerTest extends UnitTestCase {

  /**
   * The TypedDataNormalizer instance.
   *
   * @var \Drupal\serialization\Normalizer\TypedDataNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->normalizer = new PrimitiveDataNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   * @dataProvider dataProviderPrimitiveData
   */
  public function testSupportsNormalization($primitive_data, $expected) {
    $this->assertTrue($this->normalizer->supportsNormalization($primitive_data));
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalizationFail() {
    // Test that an object not implementing PrimitiveInterface fails.
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * @covers ::normalize
   * @dataProvider dataProviderPrimitiveData
   */
  public function testNormalize($primitive_data, $expected) {
    $this->assertSame($expected, $this->normalizer->normalize($primitive_data));
  }

  /**
   * Data provider for testNormalize().
   */
  public function dataProviderPrimitiveData() {
    $data = [];

    $definition = DataDefinition::createFromDataType('string');
    $string = new StringData($definition, 'string');
    $string->setValue('test');

    $data['string'] = [$string, 'test'];

    $definition = DataDefinition::createFromDataType('string');
    $string = new StringData($definition, 'string');
    $string->setValue(NULL);

    $data['string-null'] = [$string, NULL];

    $definition = DataDefinition::createFromDataType('integer');
    $integer = new IntegerData($definition, 'integer');
    $integer->setValue(5);

    $data['integer'] = [$integer, 5];

    $definition = DataDefinition::createFromDataType('integer');
    $integer = new IntegerData($definition, 'integer');
    $integer->setValue(NULL);

    $data['integer-null'] = [$integer, NULL];

    $definition = DataDefinition::createFromDataType('boolean');
    $boolean = new BooleanData($definition, 'boolean');
    $boolean->setValue(TRUE);

    $data['boolean'] = [$boolean, TRUE];

    $definition = DataDefinition::createFromDataType('boolean');
    $boolean = new BooleanData($definition, 'boolean');
    $boolean->setValue(NULL);

    $data['boolean-null'] = [$boolean, NULL];

    return $data;
  }

}
