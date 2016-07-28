<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\MethodFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\MethodFilter
 * @group Routing
 */
class MethodFilterTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::applies
   * @dataProvider providerApplies
   */
  public function testApplies(array $route_methods, $expected_applies) {
    $route = new Route('/test', [], [], [], '', [], $route_methods);
    $method_filter = new MethodFilter();

    $this->assertSame($expected_applies, $method_filter->applies($route));
  }

  /**
   * Data provider for testApplies().
   *
   * @return array
   */
  public function providerApplies() {
    return [
      'only GET' => [['GET'], TRUE],
      'only PATCH' => [['PATCH'], TRUE],
      'only POST' => [['POST'], TRUE],
      'only DELETE' => [['DELETE'], TRUE],
      'only HEAD' => [['HEAD'], TRUE],
      'all' => [['GET', 'PATCH', 'POST', 'DELETE', 'HEAD'], TRUE],
      'none' => [[], FALSE],
    ];
  }

  /**
   * @covers ::filter
   */
  public function testWithAllowedMethod() {
    $request = Request::create('/test', 'GET');
    $collection = new RouteCollection();
    $collection->add('test_route.get', new Route('/test', [], [], [], '', [], ['GET']));
    $collection_before = clone $collection;

    $method_filter = new MethodFilter();
    $result_collection = $method_filter->filter($collection, $request);

    $this->assertEquals($collection_before, $result_collection);
  }

  /**
   * @covers ::filter
   */
  public function testWithAllowedMethodAndMultipleMatchingRoutes() {
    $request = Request::create('/test', 'GET');
    $collection = new RouteCollection();
    $collection->add('test_route.get', new Route('/test', [], [], [], '', [], ['GET']));
    $collection->add('test_route2.get', new Route('/test', [], [], [], '', [], ['GET']));
    $collection->add('test_route3.get', new Route('/test', [], [], [], '', [], ['GET']));

    $collection_before = clone $collection;

    $method_filter = new MethodFilter();
    $result_collection = $method_filter->filter($collection, $request);

    $this->assertEquals($collection_before, $result_collection);
  }

  /**
   * @covers ::filter
   */
  public function testMethodNotAllowedException() {
    $request = Request::create('/test', 'PATCH');
    $collection = new RouteCollection();
    $collection->add('test_route.get', new Route('/test', [], [], [], '', [], ['GET']));

    $this->setExpectedException(MethodNotAllowedException::class);

    $method_filter = new MethodFilter();
    $method_filter->filter($collection, $request);
  }

  /**
   * @covers ::filter
   */
  public function testMethodNotAllowedExceptionWithMultipleRoutes() {
    $request = Request::create('/test', 'PATCH');
    $collection = new RouteCollection();
    $collection->add('test_route.get', new Route('/test', [], [], [], '', [], ['GET']));
    $collection->add('test_route2.get', new Route('/test', [], [], [], '', [], ['GET']));
    $collection->add('test_route3.get', new Route('/test', [], [], [], '', [], ['GET']));

    $this->setExpectedException(MethodNotAllowedException::class);

    $method_filter = new MethodFilter();
    $method_filter->filter($collection, $request);
  }

  /**
   * @covers ::filter
   */
  public function testFilteredMethods() {
    $request = Request::create('/test', 'PATCH');
    $collection = new RouteCollection();
    $collection->add('test_route.get', new Route('/test', [], [], [], '', [], ['GET']));
    $collection->add('test_route2.get', new Route('/test', [], [], [], '', [], ['PATCH']));
    $collection->add('test_route3.get', new Route('/test', [], [], [], '', [], ['POST']));

    $expected_collection = new RouteCollection();
    $expected_collection->add('test_route2.get', new Route('/test', [], [], [], '', [], ['PATCH']));

    $method_filter = new MethodFilter();
    $result_collection = $method_filter->filter($collection, $request);

    $this->assertEquals($expected_collection, $result_collection);
  }

}
