<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\RandomGeneratorTrait is deprecated in Drupal 8.1.1, will be removed before Drupal 9.0.0. Use \Drupal\Tests\RandomGeneratorTrait instead.', E_USER_DEPRECATED);

use Drupal\Tests\RandomGeneratorTrait as BaseGeneratorTrait;

/**
 * Provides random generator utility methods.
 *
 * @deprecated in drupal:8.1.1 and is removed from drupal:9.0.0. Use
 *   \Drupal\Tests\RandomGeneratorTrait instead.
 *
 * @see \Drupal\Tests
 */
trait RandomGeneratorTrait {
  use BaseGeneratorTrait;

}
