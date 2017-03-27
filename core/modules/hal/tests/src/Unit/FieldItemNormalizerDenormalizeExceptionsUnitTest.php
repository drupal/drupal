<?php

namespace Drupal\Tests\hal\Unit;

use Drupal\hal\Normalizer\FieldItemNormalizer;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\hal\Normalizer\FieldItemNormalizer
 * @group hal
 */
class FieldItemNormalizerDenormalizeExceptionsUnitTest extends NormalizerDenormalizeExceptionsUnitTestBase {

  /**
   * Tests that the FieldItemNormalizer::denormalize() throws proper exceptions.
   *
   * @param array $context
   *   Context for FieldItemNormalizer::denormalize().
   *
   * @dataProvider providerNormalizerDenormalizeExceptions
   */
  public function testFieldItemNormalizerDenormalizeExceptions($context) {
    $field_item_normalizer = new FieldItemNormalizer();
    $data = [];
    $class = [];
    $this->setExpectedException(InvalidArgumentException::class);
    $field_item_normalizer->denormalize($data, $class, NULL, $context);
  }

}
