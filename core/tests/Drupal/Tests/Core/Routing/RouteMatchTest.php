<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\RouteMatchTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Routing\RouteMatch
 * @group Routing
 */
class RouteMatchTest extends RouteMatchTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getRouteMatch($name, Route $route, array $parameters, array $raw_parameters) {
    return new RouteMatch($name, $route, $parameters, $raw_parameters);
  }

  /**
   * @covers ::createFromRequest
   * @covers ::__construct
   */
  public function testRouteMatchFromRequest() {
    $request = new Request();

    // A request that hasn't been routed yet.
    $route_match = RouteMatch::createFromRequest($request);
    $this->assertNull($route_match->getRouteName());
    $this->assertNull($route_match->getRouteObject());
    $this->assertSame(array(), $route_match->getParameters()->all());
    $this->assertNull($route_match->getParameter('foo'));
    $this->assertSame(array(), $route_match->getRawParameters()->all());
    $this->assertNull($route_match->getRawParameter('foo'));

    // A routed request without parameter upcasting.
    $route = new Route('/test-route/{foo}');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_route');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->attributes->set('foo', '1');
    $route_match = RouteMatch::createFromRequest($request);
    $this->assertSame('test_route', $route_match->getRouteName());
    $this->assertSame($route, $route_match->getRouteObject());
    $this->assertSame(array('foo' => '1'), $route_match->getParameters()->all());
    $this->assertSame(array(), $route_match->getRawParameters()->all());

    // A routed request with parameter upcasting.
    $foo = new \stdClass();
    $foo->value = 1;
    $request->attributes->set('foo', $foo);
    $request->attributes->set('_raw_variables', new ParameterBag(array('foo' => '1')));
    $route_match = RouteMatch::createFromRequest($request);
    $this->assertSame(array('foo' => $foo), $route_match->getParameters()->all());
    $this->assertSame(array('foo' => '1'), $route_match->getRawParameters()->all());
  }

}
