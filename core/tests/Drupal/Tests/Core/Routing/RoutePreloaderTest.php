<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RoutePreloader;
use Drupal\Tests\UnitTestCase;
use Drupal\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\RoutePreloader
 * @group Routing
 */
class RoutePreloaderTest extends UnitTestCase {

  /**
   * The mocked preloadable route provider.
   *
   * @var \Drupal\Core\Routing\PreloadableRouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeProvider;

  /**
   * The mocked state.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * The tested preloader.
   *
   * @var \Drupal\Core\Routing\RoutePreloader
   */
  protected $preloader;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->routeProvider = $this->createMock('Drupal\Core\Routing\PreloadableRouteProviderInterface');
    $this->state = $this->createMock('\Drupal\Core\State\StateInterface');
    $this->preloader = new RoutePreloader($this->routeProvider, $this->state);
  }

  /**
   * Tests onAlterRoutes with just admin routes.
   */
  public function testOnAlterRoutesWithAdminRoutes(): void {
    $event = $this->getMockBuilder('Drupal\Core\Routing\RouteBuildEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $route_collection = new RouteCollection();
    $route_collection->add('test', new Route('/admin/foo', ['_controller' => 'Drupal\ExampleController']));
    $route_collection->add('test2', new Route('/admin/bar', ['_controller' => 'Drupal\ExampleController']));
    $event->expects($this->once())
      ->method('getRouteCollection')
      ->willReturn($route_collection);

    $this->state->expects($this->once())
      ->method('set')
      ->with('routing.non_admin_routes', []);
    $this->preloader->onAlterRoutes($event);
    $this->preloader->onFinishedRoutes(new Event());
  }

  /**
   * Tests onAlterRoutes with "admin" appearing in the path.
   */
  public function testOnAlterRoutesWithAdminPathNoAdminRoute(): void {
    $event = $this->getMockBuilder('Drupal\Core\Routing\RouteBuildEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $route_collection = new RouteCollection();
    $route_collection->add('test', new Route('/foo/admin/foo', ['_controller' => 'Drupal\ExampleController']));
    $route_collection->add('test2', new Route('/bar/admin/bar', ['_controller' => 'Drupal\ExampleController']));
    $route_collection->add('test3', new Route('/administrator/a', ['_controller' => 'Drupal\ExampleController']));
    $route_collection->add('test4', new Route('/admin', ['_controller' => 'Drupal\ExampleController']));
    $event->expects($this->once())
      ->method('getRouteCollection')
      ->willReturn($route_collection);

    $this->state->expects($this->once())
      ->method('set')
      ->with('routing.non_admin_routes', ['test', 'test2', 'test3']);
    $this->preloader->onAlterRoutes($event);
    $this->preloader->onFinishedRoutes(new Event());
  }

  /**
   * Tests onAlterRoutes with admin routes and non admin routes.
   */
  public function testOnAlterRoutesWithNonAdminRoutes(): void {
    $event = $this->getMockBuilder('Drupal\Core\Routing\RouteBuildEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $route_collection = new RouteCollection();
    $route_collection->add('test', new Route('/admin/foo', ['_controller' => 'Drupal\ExampleController']));
    $route_collection->add('test2', new Route('/bar', ['_controller' => 'Drupal\ExampleController']));
    // Non content routes, like ajax callbacks should be ignored.
    $route3 = new Route('/bar', ['_controller' => 'Drupal\ExampleController']);
    $route3->setMethods(['POST']);
    $route_collection->add('test3', $route3);
    // Routes with the option _admin_route set to TRUE will be included.
    $route4 = new Route('/bar', ['_controller' => 'Drupal\ExampleController']);
    $route4->setOption('_admin_route', TRUE);
    $route_collection->add('test4', $route4);
    // Non-HTML routes, like api_json routes should be ignored.
    $route5 = new Route('/bar', ['_controller' => 'Drupal\ExampleController']);
    $route5->setRequirement('_format', 'api_json');
    $route_collection->add('test5', $route5);
    // Routes which include HTML should be included.
    $route6 = new Route('/bar', ['_controller' => 'Drupal\ExampleController']);
    $route6->setRequirement('_format', 'json_api|html');
    $route_collection->add('test6', $route6);

    $event->expects($this->once())
      ->method('getRouteCollection')
      ->willReturn($route_collection);

    $this->state->expects($this->once())
      ->method('set')
      ->with('routing.non_admin_routes', ['test2', 'test4', 'test6']);
    $this->preloader->onAlterRoutes($event);
    $this->preloader->onFinishedRoutes(new Event());
  }

  /**
   * Tests onRequest on a non html request.
   */
  public function testOnRequestNonHtml(): void {
    $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\KernelEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $request = new Request();
    $request->setRequestFormat('non-html');
    $event->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $this->routeProvider->expects($this->never())
      ->method('getRoutesByNames');
    $this->state->expects($this->never())
      ->method('get');

    $this->preloader->onRequest($event);
  }

  /**
   * Tests onRequest on a html request.
   */
  public function testOnRequestOnHtml(): void {
    $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\KernelEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $request = new Request();
    $request->setRequestFormat('html');
    $event->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $this->routeProvider->expects($this->once())
      ->method('preLoadRoutes')
      ->with(['test2']);
    $this->state->expects($this->once())
      ->method('get')
      ->with('routing.non_admin_routes')
      ->willReturn(['test2']);

    $this->preloader->onRequest($event);
  }

  /**
   * @group legacy
   */
  public function testConstructorDeprecation(): void {
    $this->expectDeprecation('Passing a cache bin to Drupal\Core\Routing\RoutePreloader::__construct is deprecated in drupal:10.3.0 and will be removed before drupal:11.0.0. Caching is now managed by the state service. See https://www.drupal.org/node/3177901');
    new RoutePreloader($this->routeProvider, $this->state, $this->createMock('Drupal\Core\Cache\CacheBackendInterface'));
  }

}
