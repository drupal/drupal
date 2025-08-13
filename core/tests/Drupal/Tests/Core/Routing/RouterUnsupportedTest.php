<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\Router;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Routing\Router
 * @group Routing
 * @group legacy
 */
class RouterUnsupportedTest extends UnitTestCase {

  /**
   * @covers ::generate
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
