<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\Core\TypedData\Plugin\DataType\DecimalData;
use Drupal\Core\TypedData\Plugin\DataType\DurationIso8601;
use Drupal\Core\TypedData\Plugin\DataType\Email;
use Drupal\Core\TypedData\Plugin\DataType\FloatData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\Plugin\DataType\Uri;
use Drupal\Tests\serialization\Traits\JsonSchemaTestTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\serialization\Normalizer\PrimitiveDataNormalizer;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\PrimitiveDataNormalizer
 * @group serialization
 */
class PrimitiveDataNormalizerTest extends UnitTestCase {

  use JsonSchemaTestTrait;

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
    parent::setUp();

    $this->normalizer = new PrimitiveDataNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   * @dataProvider dataProviderPrimitiveData
   */
  public function testSupportsNormalization($primitive_data, $expected): void {
    $this->assertTrue($this->normalizer->supportsNormalization($primitive_data));
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalizationFail(): void {
    // Test that an object not implementing PrimitiveInterface fails.
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * @covers ::normalize
   * @dataProvider dataProviderPrimitiveData
   */
  public function testNormalize($primitive_data, $expected): void {
    $this->assertSame($expected, $this->normalizer->normalize($primitive_data));
  }

  /**
   * Data provider for testNormalize().
   */
  public static function dataProviderPrimitiveData() {
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

  /**
   * {@inheritdoc}
   */
  public static function jsonSchemaDataProvider(): array {
    $email = new Email(DataDefinition::createFromDataType('email'));
    $email->setValue('test@example.com');
    $float = new FloatData(DataDefinition::createFromDataType('float'));
    $float->setValue(9.99);
    $uri = new Uri(DataDefinition::createFromDataType('uri'));
    $uri->setValue('https://example.com');
    $decimal = new DecimalData(DataDefinition::createFromDataType('decimal'));
    $decimal->setValue('9.99');
    // TimeSpan normalizes to an integer, however Iso8601 matches a format.
    $duration = new DurationIso8601(DataDefinition::createFromDataType('duration_iso8601'));
    $duration->setValue('P1D');

    return [
      'email' => [$email],
      'float' => [$float],
      'uri' => [$uri],
      'decimal' => [$decimal],
      'duration' => [$duration],
      ...array_map(fn ($value) => [$value[0]], static::dataProviderPrimitiveData()),
    ];
  }

}
