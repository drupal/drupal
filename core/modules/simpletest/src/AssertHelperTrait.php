<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\AssertHelperTrait is deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use Drupal\Tests\AssertHelperTrait instead. See https://www.drupal.org/node/2884454.', E_USER_DEPRECATED);

use Drupal\Tests\AssertHelperTrait as BaseAssertHelperTrait;

/**
 * Provides helper methods for assertions.
 *
 * @deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\AssertHelperTrait instead.
 *
 * @see https://www.drupal.org/node/2884454
 */
trait AssertHelperTrait {

  use BaseAssertHelperTrait;

}
