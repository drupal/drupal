<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides a router interface that also can match requests.
 */
interface TestRouterInterface extends RouterInterface, RequestMatcherInterface {
}
