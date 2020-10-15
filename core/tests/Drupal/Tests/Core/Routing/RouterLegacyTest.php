<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
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
class RouterLegacyTest extends UnitTestCase {

  /**
   * @covers ::generate
   */
  public function testGenerateDeprecated() {
    $this->expectDeprecation('Drupal\Core\Routing\Router::generate() is deprecated in drupal:8.3.0 and will throw an exception from drupal:10.0.0. Use the \Drupal\Core\Url object instead. See https://www.drupal.org/node/2820197');
    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $current_path_stack = $this->prophesize(CurrentPathStack::class);
    $url_generator = $this->prophesize(UrlGeneratorInterface::class);
    $route_name = 'test.route';
    $route_path = '/test';
    $url_generator
      ->generate($route_name, Argument::any(), Argument::any())
      ->willReturn($route_path);
    $router = new Router($route_provider->reveal(), $current_path_stack->reveal(), $url_generator->reveal());
    $this->assertEquals($route_path, $router->generate($route_name));
  }

}
