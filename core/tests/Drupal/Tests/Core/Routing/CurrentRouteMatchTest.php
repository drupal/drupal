<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Routing\CurrentRouteMatch
 * @group Routing
 */
class CurrentRouteMatchTest extends RouteMatchTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function getRouteMatch(string $name, Route $route, array $parameters, array $raw_parameters): RouteMatchInterface {
    $request_stack = new RequestStack();
    $request = new Request();
    $request_stack->push($request);

    $request = $request_stack->getCurrentRequest();
    $request->attributes = new ParameterBag($parameters);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $name);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->attributes->set('_raw_variables', new InputBag($raw_parameters));
    return new CurrentRouteMatch($request_stack);
  }

  /**
   * @covers ::__construct
   * @covers ::getRouteObject
   * @covers ::getCurrentRouteMatch
   * @covers ::getRouteMatch
   */
  public function testGetCurrentRouteObject(): void {

    $request_stack = new RequestStack();
    $request = new Request();
    $request_stack->push($request);
    $current_route_match = new CurrentRouteMatch($request_stack);

    // Before routing.
    $this->assertNull($current_route_match->getRouteObject());

    // After routing.
    $route = new Route('/test-route/{foo}');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_route');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->attributes->set('foo', '1');
    $this->assertSame('1', $current_route_match->getParameter('foo'));

    // Immutable for the same request once a route has been matched.
    $request->attributes->set('foo', '2');
    $this->assertSame('1', $current_route_match->getParameter('foo'));

    // Subrequest.
    $subrequest = new Request();
    $subrequest->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_subrequest_route');
    $subrequest->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test-subrequest-route/{foo}'));
    $subrequest->attributes->set('foo', '2');
    $request_stack->push($subrequest);
    $this->assertSame('2', $current_route_match->getParameter('foo'));

    // Restored original request.
    $request_stack->pop();
    $this->assertSame('1', $current_route_match->getParameter('foo'));
  }

  /**
   * @covers ::getRouteMatchFromRequest
   */
  public function testGetRouteMatchFromRequestWithRouting(): void {
    $request_stack = new RequestStack();
    $request = new Request();
    $request_stack->push($request);
    $current_route_match = new CurrentRouteMatch($request_stack);

    $route_match = $current_route_match->getRouteMatchFromRequest($request);

    $this->assertNull($route_match->getRouteName());
    $this->assertNull($route_match->getRouteObject());
  }

  /**
   * @covers ::getRouteMatchFromRequest
   */
  public function testGetRouteMatchFromRequest(): void {
    $request_stack = new RequestStack();
    $request = new Request();
    $request_stack->push($request);
    $route = new Route('/test-route/{foo}');

    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_route');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->attributes->set('foo', '1');
    $current_route_match = new CurrentRouteMatch($request_stack);

    $route_match = $current_route_match->getRouteMatchFromRequest($request);
    $this->assertEquals('test_route', $route_match->getRouteName());
    $this->assertEquals($route, $route_match->getRouteObject());
  }

  /**
   * @covers ::resetRouteMatch
   */
  public function testResetRouteMatch(): void {
    $route = new Route('/test-route/{foo}');
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_route');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request_stack = new RequestStack();
    $request_stack->push($request);

    $current_route_match = new CurrentRouteMatch($request_stack);

    $route_name = $current_route_match->getRouteName();
    $this->assertSame('test_route', $route_name);

    // Replace the matched route on the request.
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, NULL);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, NULL);
    // Reset the route match.
    $current_route_match->resetRouteMatch();

    $route_name = $current_route_match->getRouteName();
    $this->assertNull($route_name);
  }

}
