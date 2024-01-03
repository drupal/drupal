<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\serialization\Normalizer\NullNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\NullNormalizer
 * @group serialization
 */
class NullNormalizerTest extends UnitTestCase {

  /**
   * The NullNormalizer instance.
   *
   * @var \Drupal\serialization\Normalizer\NullNormalizer
   */
  protected $normalizer;

  /**
   * The interface to use in testing.
   *
   * @var string
   */
  protected $interface = 'Drupal\Core\TypedData\TypedDataInterface';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->normalizer = new NullNormalizer($this->interface);
  }

  /**
   * @covers ::__construct
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $mock = $this->createMock('Drupal\Core\TypedData\TypedDataInterface');
    $this->assertTrue($this->normalizer->supportsNormalization($mock));
    // Also test that an object not implementing TypedDataInterface fails.
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $mock = $this->createMock('Drupal\Core\TypedData\TypedDataInterface');
    $this->assertNull($this->normalizer->normalize($mock));
  }

}
