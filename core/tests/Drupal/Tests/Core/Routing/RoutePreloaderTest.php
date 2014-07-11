<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\RoutePreloaderTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RoutePreloader;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\RoutePreloader
 * @group Routing
 */
class RoutePreloaderTest extends UnitTestCase {

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The mocked state.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $state;

  /**
   * The mocked content negotiator.
   *
   * @var \Drupal\Core\ContentNegotiation|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $negotiation;

  /**
   * The tested preloader.
   *
   * @var \Drupal\Core\Routing\RoutePreloader
   */
  protected $preloader;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->state = $this->getMock('\Drupal\Core\State\StateInterface');
    $this->negotiation = $this->getMockBuilder('\Drupal\Core\ContentNegotiation')
      ->disableOriginalConstructor()
      ->getMock();
    $this->preloader = new RoutePreloader($this->routeProvider, $this->state, $this->negotiation);
  }

  /**
   * Tests onAlterRoutes with just admin routes.
   */
  public function testOnAlterRoutesWithAdminRoutes() {
    $event = $this->getMockBuilder('Drupal\Core\Routing\RouteBuildEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $route_collection = new RouteCollection();
    $route_collection->add('test', new Route('/admin/foo', array('_content' => 'Drupal\ExampleController')));
    $route_collection->add('test2', new Route('/admin/bar', array('_content' => 'Drupal\ExampleController')));
    $event->expects($this->once())
      ->method('getRouteCollection')
      ->will($this->returnValue($route_collection));

    $this->state->expects($this->once())
      ->method('set')
      ->with('routing.non_admin_routes', array());
    $this->preloader->onAlterRoutes($event);
    $this->preloader->onFinishedRoutes(new Event());
  }

  /**
   * Tests onAlterRoutes with "admin" appearing in the path.
   */
  public function testOnAlterRoutesWithAdminPathNoAdminRoute() {
    $event = $this->getMockBuilder('Drupal\Core\Routing\RouteBuildEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $route_collection = new RouteCollection();
    $route_collection->add('test', new Route('/foo/admin/foo', array('_content' => 'Drupal\ExampleController')));
    $route_collection->add('test2', new Route('/bar/admin/bar', array('_content' => 'Drupal\ExampleController')));
    $route_collection->add('test3', new Route('/administrator/a', array('_content' => 'Drupal\ExampleController')));
    $route_collection->add('test4', new Route('/admin', array('_content' => 'Drupal\ExampleController')));
    $event->expects($this->once())
      ->method('getRouteCollection')
      ->will($this->returnValue($route_collection));

    $this->state->expects($this->once())
      ->method('set')
      ->with('routing.non_admin_routes', array('test', 'test2', 'test3'));
    $this->preloader->onAlterRoutes($event);
    $this->preloader->onFinishedRoutes(new Event());
  }


  /**
   * Tests onAlterRoutes with admin routes and non admin routes.
   */
  public function testOnAlterRoutesWithNonAdminRoutes() {
    $event = $this->getMockBuilder('Drupal\Core\Routing\RouteBuildEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $route_collection = new RouteCollection();
    $route_collection->add('test', new Route('/admin/foo', array('_content' => 'Drupal\ExampleController')));
    $route_collection->add('test2', new Route('/bar', array('_content' => 'Drupal\ExampleController')));
    // Non content routes, like ajax callbacks should be ignored.
    $route_collection->add('test3', new Route('/bar', array('_controller' => 'Drupal\ExampleController')));
    $event->expects($this->once())
      ->method('getRouteCollection')
      ->will($this->returnValue($route_collection));

    $this->state->expects($this->once())
      ->method('set')
      ->with('routing.non_admin_routes', array('test2', 'test3'));
    $this->preloader->onAlterRoutes($event);
    $this->preloader->onFinishedRoutes(new Event());
  }

  /**
   * Tests onRequest on a non html request.
   */
  public function testOnRequestNonHtml() {
    $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\KernelEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $request = new Request();
    $event->expects($this->any())
      ->method('getRequest')
      ->will($this->returnValue($request));
    $this->negotiation->expects($this->once())
      ->method('getContentType')
      ->will($this->returnValue('non-html'));

    $this->routeProvider->expects($this->never())
      ->method('getRoutesByNames');
    $this->state->expects($this->never())
      ->method('get');

    $this->preloader->onRequest($event);
  }

  /**
   * Tests onRequest on a html request.
   */
  public function testOnRequestOnHtml() {
    $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\KernelEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $request = new Request();
    $event->expects($this->any())
      ->method('getRequest')
      ->will($this->returnValue($request));
    $this->negotiation->expects($this->once())
      ->method('getContentType')
      ->will($this->returnValue('html'));

    $this->routeProvider->expects($this->once())
      ->method('getRoutesByNames')
      ->with(array('test2'));
    $this->state->expects($this->once())
      ->method('get')
      ->with('routing.non_admin_routes')
      ->will($this->returnValue(array('test2')));

    $this->preloader->onRequest($event);
  }

}
