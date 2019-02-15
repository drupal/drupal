<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Tests\UnitTestCase;

/**
 * Tests that TimeStampItemNormalizerTrait throws a deprecation error.
 *
 * @group serialization
 * @group legacy
 * @coversDefaultClass \Drupal\serialization\Normalizer\TimeStampItemNormalizerTrait
 */
class TimeStampItemNormalizerTraitDeprecatedTest extends UnitTestCase {

  /**
   * Tests that TimeStampItemNormalizerTrait throws a deprecation error.
   *
   * @expectedDeprecation Drupal\serialization\Normalizer\TimeStampItemNormalizerTrait is deprecated in Drupal 8.7.0 and will be removed in Drupal 9.0.0. Use \Drupal\serialization\Normalizer\TimestampNormalizer instead.
   */
  public function testDeprecated() {
    $test = new TimeStampItemNormalizerTraitDeprecatedTestClass();
  }

}
