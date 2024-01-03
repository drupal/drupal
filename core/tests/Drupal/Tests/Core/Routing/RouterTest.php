<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\Router;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\Router
 * @group Routing
 */
class RouterTest extends UnitTestCase {

  /**
   * @covers ::applyFitOrder
   */
  public function testMatchesWithDifferentFitOrder() {
    $route_provider = $this->prophesize(RouteProviderInterface::class);

    $route_collection = new RouteCollection();

    $route = new Route('/user/{user}');
    $route->setOption('compiler_class', RouteCompiler::class);
    $route_collection->add('user_view', $route);

    $route = new Route('/user/login');
    $route->setOption('compiler_class', RouteCompiler::class);
    $route_collection->add('user_login', $route);

    $route_provider->getRouteCollectionForRequest(Argument::any())
      ->willReturn($route_collection);

    $url_generator = $this->prophesize(UrlGeneratorInterface::class);
    $current_path_stack = $this->prophesize(CurrentPathStack::class);
    $router = new Router($route_provider->reveal(), $current_path_stack->reveal(), $url_generator->reveal());

    $request_context = $this->createMock(RequestContext::class);
    $request_context->expects($this->any())
      ->method('getScheme')
      ->willReturn('http');
    $router->setContext($request_context);

    $current_path_stack->getPath(Argument::any())->willReturn('/user/1');
    $result = $router->match('/user/1');

    $this->assertEquals('user_view', $result['_route']);

    $current_path_stack->getPath(Argument::any())->willReturn('/user/login');
    $result = $router->match('/user/login');

    $this->assertEquals('user_login', $result['_route']);
  }

}
