<?php

namespace Drupal\Tests\hal\Unit;

use Drupal\hal\Normalizer\FieldItemNormalizer;

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
   * @expectedException \Symfony\Component\Serializer\Exception\InvalidArgumentException
   */
  public function testFieldItemNormalizerDenormalizeExceptions($context) {
    $field_item_normalizer = new FieldItemNormalizer();
    $data = array();
    $class = array();
    $field_item_normalizer->denormalize($data, $class, NULL, $context);
  }

}
