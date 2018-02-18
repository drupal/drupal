<?php

namespace Drupal\simpletest;

use Drupal\KernelTests\RouteProvider as CoreRouteProvider;

/**
 * Rebuilds the router when the provider is instantiated.
 *
 * @deprecated in 8.6.0 for removal before 9.0.0. Use
 *   Drupal\KernelTests\RouteProvider instead.
 *
 * @see https://www.drupal.org/node/2943146
 */
class RouteProvider extends CoreRouteProvider {
}
