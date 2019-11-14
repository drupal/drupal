<?php

namespace Drupal\simpletest;

use Drupal\KernelTests\RouteProvider as CoreRouteProvider;

/**
 * Rebuilds the router when the provider is instantiated.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\KernelTests\RouteProvider instead.
 *
 * @see https://www.drupal.org/node/2943146
 */
class RouteProvider extends CoreRouteProvider {
}
