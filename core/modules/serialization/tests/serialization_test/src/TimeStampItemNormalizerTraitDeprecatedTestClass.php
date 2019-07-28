<?php

namespace Drupal\serialization_test;

use Drupal\serialization\Normalizer\TimeStampItemNormalizerTrait;

/**
 * For testing that TimeStampItemNormalizerTrait throws a deprecation error.
 *
 * @see \Drupal\Tests\serialization\Unit\Normalizer\TimeStampItemNormalizerTraitDeprecatedTest
 */
class TimeStampItemNormalizerTraitDeprecatedTestClass {
  use TimeStampItemNormalizerTrait;

}
