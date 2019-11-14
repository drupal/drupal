<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\SessionTestTrait is deprecated in Drupal 8.1.1 will be removed before 9.0.0. Use \Drupal\Tests\SessionTestTrait instead.', E_USER_DEPRECATED);

use Drupal\Tests\SessionTestTrait as BaseSessionTestTrait;

/**
 * Provides methods to generate and get session name in tests.
 *
 * @deprecated in drupal:8.1.1 and is removed from drupal:9.0.0. Use
 *   \Drupal\Tests\SessionTestTrait instead.
 *
 * @see \Drupal\Tests
 */
trait SessionTestTrait {

  use BaseSessionTestTrait;

}
