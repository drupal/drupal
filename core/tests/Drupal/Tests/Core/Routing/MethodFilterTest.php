<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\MethodFilter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\MethodFilter
 * @group Routing
 */
class MethodFilterTest extends UnitTestCase {

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

    $this->expectException(MethodNotAllowedException::class);

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

    $this->expectException(MethodNotAllowedException::class);

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

  /**
   * Ensures that the incoming and outgoing collections have the same order.
   *
   * @covers ::filter
   */
  public function testCollectionOrder() {
    $request = Request::create('/test', 'GET');

    $collection = new RouteCollection();
    $collection->add('entity.taxonomy_term.canonical', new Route('/test'));
    $collection->add('views.view.taxonomy_term_page', new Route('/test', [], [], [], '', [], ['GET', 'POST']));

    $method_filter = new MethodFilter();
    $result_collection = $method_filter->filter($collection, $request);

    $this->assertEquals(['entity.taxonomy_term.canonical', 'views.view.taxonomy_term_page'], array_keys($result_collection->all()));
  }

}
