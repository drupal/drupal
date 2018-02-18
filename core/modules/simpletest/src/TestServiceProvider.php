<?php

namespace Drupal\simpletest;

use Drupal\KernelTests\TestServiceProvider as CoreTestServiceProvider;

/**
 * Provides special routing services for tests.
 *
 * @deprecated in 8.6.0 for removal before Drupal 9.0.0. Use
 *   Drupal\KernelTests\TestServiceProvider instead.
 *
 * @see https://www.drupal.org/node/2943146
 */
class TestServiceProvider extends CoreTestServiceProvider {

}
