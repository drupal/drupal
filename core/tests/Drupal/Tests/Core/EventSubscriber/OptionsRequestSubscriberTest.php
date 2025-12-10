<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\OptionsRequestSubscriber;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests Drupal\Core\EventSubscriber\OptionsRequestSubscriber.
 */
#[CoversClass(OptionsRequestSubscriber::class)]
#[Group('EventSubscriber')]
class OptionsRequestSubscriberTest extends UnitTestCase {

  /**
   * Tests with non option request.
   *
   * @legacy-covers ::onRequest
   */
  public function testWithNonOptionRequest(): void {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/example', 'GET');

    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $route_provider->getRouteCollectionForRequest($request)->shouldNotBeCalled();

    $subscriber = new OptionsRequestSubscriber($route_provider->reveal());
    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MAIN_REQUEST);
    $subscriber->onRequest($event);

    $this->assertFalse($event->hasResponse());
  }

  /**
   * Tests without matching routes.
   *
   * @legacy-covers ::onRequest
   */
  public function testWithoutMatchingRoutes(): void {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/example', 'OPTIONS');

    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $route_provider->getRouteCollectionForRequest($request)->willReturn(new RouteCollection())->shouldBeCalled();

    $subscriber = new OptionsRequestSubscriber($route_provider->reveal());
    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MAIN_REQUEST);
    $subscriber->onRequest($event);

    $this->assertFalse($event->hasResponse());
  }

  /**
   * Tests with options request.
   *
   * @legacy-covers ::onRequest
   */
  #[DataProvider('providerTestOnRequestWithOptionsRequest')]
  public function testWithOptionsRequest(RouteCollection $collection, $expected_header): void {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/example', 'OPTIONS');

    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $route_provider->getRouteCollectionForRequest($request)->willReturn($collection)->shouldBeCalled();

    $subscriber = new OptionsRequestSubscriber($route_provider->reveal());
    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MAIN_REQUEST);
    $subscriber->onRequest($event);

    $this->assertTrue($event->hasResponse());
    $response = $event->getResponse();
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals($expected_header, $response->headers->get('Allow'));
  }

  public static function providerTestOnRequestWithOptionsRequest() {
    $data = [];

    foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $method) {
      $collection = new RouteCollection();
      $collection->add('example.1', new Route('/example', [], [], [], '', [], [$method]));
      $data['one_route_' . $method] = [$collection, $method];
    }

    foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $method_a) {
      foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $method_b) {
        if ($method_a != $method_b) {
          $collection = new RouteCollection();
          $collection->add('example.1', new Route('/example', [], [], [], '', [], [$method_a, $method_b]));
          $data['one_route_' . $method_a . '_' . $method_b] = [$collection, $method_a . ', ' . $method_b];
        }
      }
    }

    foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $method_a) {
      foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $method_b) {
        foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $method_c) {
          $collection = new RouteCollection();
          $collection->add('example.1', new Route('/example', [], [], [], '', [], [$method_a]));
          $collection->add('example.2', new Route('/example', [], [], [], '', [], [$method_a, $method_b]));
          $collection->add('example.3', new Route('/example', [], [], [], '', [], [$method_b, $method_c]));
          $methods = array_unique([$method_a, $method_b, $method_c]);
          $data['multiple_routes_' . $method_a . '_' . $method_b . '_' . $method_c] = [
            $collection,
            implode(', ', $methods),
          ];
        }
      }
    }

    return $data;
  }

}
