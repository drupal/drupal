<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Tests\UnitTestCase;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\NormalizerBase
 * @group serialization
 */
class NormalizerBaseTest extends UnitTestCase {

  /**
   * Tests the supportsNormalization method.
   *
   * @dataProvider providerTestSupportsNormalization
   *
   * @param bool $expected_return
   *   The expected boolean return value from supportNormalization.
   * @param mixed $data
   *   The data passed to supportsNormalization.
   * @param string $supported_types
   *   (optional) The supported interface or class to set on the normalizer.
   */
  public function testSupportsNormalization($expected_return, $data, $supported_types = NULL): void {
    $normalizer_base = new TestNormalizerBase();

    if (isset($supported_types)) {
      $normalizer_base->setSupportedTypes($supported_types);
    }

    $this->assertSame($expected_return, $normalizer_base->supportsNormalization($data));
  }

  /**
   * Data provider for testSupportsNormalization.
   *
   * @return array
   *   An array of provider data for testSupportsNormalization.
   */
  public static function providerTestSupportsNormalization() {
    return [
      // Something that is not an object should return FALSE immediately.
      [FALSE, []],
      // An object with no class set should return FALSE.
      [FALSE, new \stdClass()],
      // Set a supported Class.
      [TRUE, new \stdClass(), 'stdClass'],
      // Set a supported interface.
      [TRUE, new \RecursiveArrayIterator(), 'RecursiveIterator'],
      // Set a different class.
      [FALSE, new \stdClass(), 'ArrayIterator'],
      // Set a different interface.
      [FALSE, new \stdClass(), 'RecursiveIterator'],
    ];
  }

}

/**
 * Testable class for NormalizerBase.
 */
class TestNormalizerBase extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string[]
   */
  protected array $supportedTypes = ['*' => FALSE];

  /**
   * Sets the supported types.
   *
   * @param string $supported_types
   *   The class name to set.
   */
  public function setSupportedTypes($supported_types): void {
    $this->supportedTypes = [$supported_types => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return $this->supportedTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return NULL;
  }

}
