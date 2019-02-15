<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\serialization\Normalizer\TimeStampItemNormalizerTrait;

/**
 * For testing that TimeStampItemNormalizerTrait throws a deprecation error.
 *
 * @see \Drupal\Tests\serialization\Unit\Normalizer\TimeStampItemNormalizerTraitDeprecatedTest
 */
class TimeStampItemNormalizerTraitDeprecatedTestClass {
  use TimeStampItemNormalizerTrait;

}
