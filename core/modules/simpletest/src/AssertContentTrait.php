<?php

namespace Drupal\simpletest;

use Drupal\KernelTests\AssertContentTrait as CoreAssertContentTrait;

/**
 * Provides test methods to assert content.
 *
 * @deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use
 *   Drupal\KernelTests\AssertContentTrait instead.
 *
 * @see https://www.drupal.org/node/2943146
 */
trait AssertContentTrait {

  use CoreAssertContentTrait;

}
