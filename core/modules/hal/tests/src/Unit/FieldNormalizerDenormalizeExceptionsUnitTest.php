<?php

namespace Drupal\Tests\hal\Unit;

use Drupal\hal\Normalizer\FieldNormalizer;

/**
 * @coversDefaultClass \Drupal\hal\Normalizer\FieldNormalizer
 * @group hal
 */
class FieldNormalizerDenormalizeExceptionsUnitTest extends NormalizerDenormalizeExceptionsUnitTestBase {

  /**
   * Tests that the FieldNormalizer::denormalize() throws proper exceptions.
   *
   * @param array $context
   *   Context for FieldNormalizer::denormalize().
   *
   * @dataProvider providerNormalizerDenormalizeExceptions
   * @expectedException \Symfony\Component\Serializer\Exception\InvalidArgumentException
   */
  public function testFieldNormalizerDenormalizeExceptions($context) {
    $field_item_normalizer = new FieldNormalizer();
    $data = array();
    $class = array();
    $field_item_normalizer->denormalize($data, $class, NULL, $context);
  }

}
