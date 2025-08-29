<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\Core\Routing\RouteMatch.
 */
#[CoversClass(RouteMatch::class)]
#[Group('Routing')]
class RouteMatchTest extends RouteMatchTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function getRouteMatch(string $name, Route $route, array $parameters, array $raw_parameters): RouteMatchInterface {
    return new RouteMatch($name, $route, $parameters, $raw_parameters);
  }

  /**
   * Tests route match from request.
   *
   * @legacy-covers ::createFromRequest
   * @legacy-covers ::__construct
   */
  public function testRouteMatchFromRequest(): void {
    $request = new Request();

    // A request that hasn't been routed yet.
    $route_match = RouteMatch::createFromRequest($request);
    $this->assertNull($route_match->getRouteName());
    $this->assertNull($route_match->getRouteObject());
    $this->assertSame([], $route_match->getParameters()->all());
    $this->assertNull($route_match->getParameter('foo'));
    $this->assertSame([], $route_match->getRawParameters()->all());
    $this->assertNull($route_match->getRawParameter('foo'));

    // A routed request without parameter upcasting.
    $route = new Route('/test-route/{foo}');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_route');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->attributes->set('foo', '1');
    $route_match = RouteMatch::createFromRequest($request);
    $this->assertSame('test_route', $route_match->getRouteName());
    $this->assertSame($route, $route_match->getRouteObject());
    $this->assertSame(['foo' => '1'], $route_match->getParameters()->all());
    $this->assertSame([], $route_match->getRawParameters()->all());

    // A routed request with parameter upcasting.
    $foo = new \stdClass();
    $foo->value = 1;
    $request->attributes->set('foo', $foo);
    $request->attributes->set('_raw_variables', new InputBag(['foo' => '1']));
    $route_match = RouteMatch::createFromRequest($request);
    $this->assertSame(['foo' => $foo], $route_match->getParameters()->all());
    $this->assertSame(['foo' => '1'], $route_match->getRawParameters()->all());
  }

}
