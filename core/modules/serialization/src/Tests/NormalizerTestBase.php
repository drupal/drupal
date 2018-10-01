<?php

namespace Drupal\serialization\Tests;

@trigger_error(__NAMESPACE__ . '\NormalizerTestBase is deprecated for removal before Drupal 9.0.0. Use \Drupal\Tests\serialization\Kernel\NormalizerTestBase instead.', E_USER_DEPRECATED);

use Drupal\Tests\serialization\Kernel\NormalizerTestBase as SerializationNormalizerTestBase;

/**
 * Helper base class to set up some test fields for serialization testing.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0.
 *   Use \Drupal\Tests\serialization\Kernel\NormalizerTestBase instead.
 */
abstract class NormalizerTestBase extends SerializationNormalizerTestBase {}
