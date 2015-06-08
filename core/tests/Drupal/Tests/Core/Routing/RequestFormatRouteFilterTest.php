<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\RequestFormatRouteFilterTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RequestFormatRouteFilter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\RequestFormatRouteFilter
 * @group Routing
 */
class RequestFormatRouteFilterTest extends UnitTestCase {

  /**
   * @covers ::applies
   */
  public function testAppliesWithoutFormat() {
    $route_filter = new RequestFormatRouteFilter();
    $route = new Route('/test');
    $this->assertFalse($route_filter->applies($route));
  }

  /**
   * @covers ::applies
   */
  public function testAppliesWithFormat() {
    $route_filter = new RequestFormatRouteFilter();
    $route = new Route('/test');
    $route->setRequirement('_format', 'json');
    $this->assertTrue($route_filter->applies($route));
  }

  /**
   * @covers ::filter
   */
  public function testFilter() {
    $route_filter = new RequestFormatRouteFilter();

    $route_without_format = new Route('/test');
    $route_with_format = $route = new Route('/test');
    $route_with_format->setRequirement('_format', 'json');
    $route_with_multiple_formats = $route = new Route('/test');
    $route_with_multiple_formats->setRequirement('_format', 'json|xml');

    $collection = new RouteCollection();
    $collection->add('test_0', $route_without_format);
    $collection->add('test_1', $route_with_format);
    $collection->add('test_2', $route_with_multiple_formats);

    $request = new Request();
    $request->setRequestFormat('xml');
    $collection = $route_filter->filter($collection, $request);

    $this->assertCount(2, $collection);
    $this->assertEquals(array_keys($collection->all())[0], 'test_2');
    $this->assertEquals(array_keys($collection->all())[1], 'test_0');
  }

  /**
   * @covers ::filter
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
   * @expectedExceptionMessage No route found for the specified format xml.
   */
  public function testNoRouteFound() {
    $collection = new RouteCollection();
    $route_with_format = $route = new Route('/test');
    $route_with_format->setRequirement('_format', 'json');
    $collection->add('test_0', $route_with_format);
    $collection->add('test_1', clone $route_with_format);

    $request = Request::create('test?_format=xml', 'GET');
    $request->setRequestFormat('xml');
    $route_filter = new RequestFormatRouteFilter();
    $route_filter->filter($collection, $request);
  }

}
