<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\FieldItemNormalizerDenormalizeExceptionsUnitTest.
 */

namespace Drupal\hal\Tests;

use Drupal\hal\Normalizer\FieldItemNormalizer;

/**
 * @coversDefaultClass \Drupal\hal\Normalizer\FieldItemNormalizer
 *
 * @group Drupal
 * @group HAL
 */
class FieldItemNormalizerDenormalizeExceptionsUnitTest extends NormalizerDenormalizeExceptionsUnitTestBase {

  /**
   * @inheritdoc
   */
  public static function getInfo() {
    return array(
      'name' => 'FieldItemNormalizer::denormalize() Unit Test',
      'description' => 'Test that FieldItemNormalizer::denormalize() throws proper exceptions.',
      'group' => 'HAL',
    );
  }

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
