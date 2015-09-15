<?php

/**
 * @file
 * Contains \Drupal\Tests\serialization\Unit\Normalizer\ComplexDataNormalizerTest.
 */

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\ComplexDataNormalizer
 * @group serialization
 */
class ComplexDataNormalizerTest extends UnitTestCase {

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
  public function setUp() {
    $this->normalizer = new ComplexDataNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization(new TestComplexData()));
    // Also test that an object not implementing ComplexDataInterface fails.
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $context = ['test' => 'test'];

    $serializer_prophecy = $this->prophesize(Serializer::class);

    $serializer_prophecy->normalize('A', static::TEST_FORMAT, $context)
      ->shouldBeCalled();
    $serializer_prophecy->normalize('B', static::TEST_FORMAT, $context)
      ->shouldBeCalled();

    $this->normalizer->setSerializer($serializer_prophecy->reveal());

    $complex_data = new TestComplexData(['a' => 'A', 'b' => 'B']);
    $this->normalizer->normalize($complex_data, static::TEST_FORMAT, $context);

  }

}

/**
 * Test class implementing ComplexDataInterface and IteratorAggregate.
 */
class TestComplexData implements \IteratorAggregate, ComplexDataInterface {

  private $values;

  public function __construct(array $values = []) {
    $this->values = $values;
  }

  public function getIterator() {
    return new \ArrayIterator($this->values);
  }

  public function applyDefaultValue($notify = TRUE) {
  }

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
  }

  public function get($property_name) {
  }

  public function getConstraints() {
  }

  public function getDataDefinition() {
  }

  public function getName() {
  }

  public function getParent() {
  }

  public function getProperties($include_computed = FALSE) {
  }

  public function getPropertyPath() {
  }

  public function getRoot() {
  }

  public function getString() {
  }

  public function getValue() {
  }

  public function isEmpty() {
  }

  public function onChange($name) {
  }

  public function set($property_name, $value, $notify = TRUE) {
  }

  public function setContext($name = NULL, TraversableTypedDataInterface $parent = NULL) {
  }

  public function setValue($value, $notify = TRUE) {
  }

  public function toArray() {
  }

  public function validate() {
  }

}
