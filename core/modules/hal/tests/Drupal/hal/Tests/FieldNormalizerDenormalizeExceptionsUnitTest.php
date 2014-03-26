<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\FieldNormalizerDenormalizeExceptionsUnitTest.
 */

namespace Drupal\hal\Tests;

use Drupal\hal\Normalizer\FieldNormalizer;

/**
 * @coversDefaultClass \Drupal\hal\Normalizer\FieldNormalizer
 *
 * @group Drupal
 * @group HAL
 */
class FieldNormalizerDenormalizeExceptionsUnitTest extends NormalizerDenormalizeExceptionsUnitTestBase {

  /**
   * @inheritdoc
   */
  public static function getInfo() {
    return array(
      'name' => 'FieldNormalizer::denormalize() Unit Test',
      'description' => 'Test that FieldNormalizer::denormalize() throws proper exceptions.',
      'group' => 'HAL',
    );
  }

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
