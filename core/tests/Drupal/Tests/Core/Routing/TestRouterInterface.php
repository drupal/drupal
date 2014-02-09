<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\TestRouterInterface.
 */

namespace Drupal\Tests\Core\Routing;

use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides a router interface that also can match requests.
 */
interface TestRouterInterface extends RouterInterface, RequestMatcherInterface {
}
