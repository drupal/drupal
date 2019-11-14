<?php

namespace Drupal\simpletest;

use Drupal\KernelTests\AssertContentTrait as CoreAssertContentTrait;

@trigger_error('\Drupal\simpletest\AssertContentTrait is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\KernelTests\AssertContentTrait.', E_USER_DEPRECATED);

/**
 * Provides test methods to assert content.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\KernelTests\AssertContentTrait instead.
 *
 * @see https://www.drupal.org/node/2943146
 */
trait AssertContentTrait {

  use CoreAssertContentTrait;

}
