<?php

namespace Drupal\simpletest;

use Drupal\KernelTests\TestServiceProvider as CoreTestServiceProvider;

/**
 * Provides special routing services for tests.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\KernelTests\TestServiceProvider instead.
 *
 * @see https://www.drupal.org/node/2943146
 */
class TestServiceProvider extends CoreTestServiceProvider {

}
