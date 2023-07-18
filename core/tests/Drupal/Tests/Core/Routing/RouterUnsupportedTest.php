<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\EnhancerInterface;
use Drupal\Core\Routing\FilterInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\Router;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Routing\Router
 * @group Routing
 * @group legacy
 */
class RouterUnsupportedTest extends UnitTestCase {

  /**
   * @covers ::generate
   */
  public function testGenerateUnsupported() {
    $this->expectException(\BadMethodCallException::class);
    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $current_path_stack = $this->prophesize(CurrentPathStack::class);
    $url_generator = $this->prophesize(UrlGeneratorInterface::class);
    $route_name = 'test.route';
    $route_path = '/test';
    $url_generator
      ->generate($route_name, Argument::any(), Argument::any())
      ->willReturn($route_path);
    $router = new Router($route_provider->reveal(), $current_path_stack->reveal(), $url_generator->reveal());
    $router->generate($route_name);
  }

  /**
   * @covers ::addDeprecatedRouteFilter
   * @covers ::addDeprecatedRouteEnhancer
   */
  public function testDeprecatedAdd() {
    // Test needs access to router's protected properties.
    $filters = new \ReflectionProperty(Router::class, 'filters');
    $enhancers = new \ReflectionProperty(Router::class, 'enhancers');

    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $current_path_stack = $this->prophesize(CurrentPathStack::class);
    $url_generator = $this->prophesize(UrlGeneratorInterface::class);
    $router = new Router($route_provider->reveal(), $current_path_stack->reveal(), $url_generator->reveal());

    $this->expectDeprecation('non_lazy_route_filter is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use route_filter instead. See https://www.drupal.org/node/2894934');
    $filter = $this->prophesize(FilterInterface::class)->reveal();
    $router->addDeprecatedRouteFilter($filter);
    $this->assertSame($filter, $filters->getValue($router)[0]);

    $this->expectDeprecation('non_lazy_route_enhancer is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use route_enhancer instead. See https://www.drupal.org/node/2894934');
    $enhancer = $this->prophesize(EnhancerInterface::class)->reveal();
    $router->addDeprecatedRouteEnhancer($enhancer);
    $this->assertSame($enhancer, $enhancers->getValue($router)[0]);
  }

}
