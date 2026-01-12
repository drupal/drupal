<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\Router;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests Drupal\Core\Routing\Router.
 */
#[CoversClass(Router::class)]
#[Group('Routing')]
#[IgnoreDeprecations]
class RouterUnsupportedTest extends UnitTestCase {

  /**
   * Tests generate unsupported.
   */
  public function testGenerateUnsupported(): void {
    $this->expectException(\BadMethodCallException::class);
    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $current_path_stack = $this->prophesize(CurrentPathStack::class);
    $route_name = 'test.route';
    $router = new Router($route_provider->reveal(), $current_path_stack->reveal());
    $router->generate($route_name);
  }

}
