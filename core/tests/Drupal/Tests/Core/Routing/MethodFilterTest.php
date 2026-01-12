<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\MethodFilter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests Drupal\Core\Routing\MethodFilter.
 */
#[CoversClass(MethodFilter::class)]
#[Group('Routing')]
class MethodFilterTest extends UnitTestCase {

  /**
   * Tests with allowed method.
   *
   * @legacy-covers ::filter
   */
  public function testWithAllowedMethod(): void {
    $request = Request::create('/test', 'GET');
    $collection = new RouteCollection();
    $collection->add('test_route.get', new Route('/test', [], [], [], '', [], ['GET']));
    $collection_before = clone $collection;

    $method_filter = new MethodFilter();
    $result_collection = $method_filter->filter($collection, $request);

    $this->assertEquals($collection_before, $result_collection);
  }

  /**
   * Tests with allowed method and multiple matching routes.
   *
   * @legacy-covers ::filter
   */
  public function testWithAllowedMethodAndMultipleMatchingRoutes(): void {
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
   * Tests method not allowed exception.
   *
   * @legacy-covers ::filter
   */
  public function testMethodNotAllowedException(): void {
    $request = Request::create('/test', 'PATCH');
    $collection = new RouteCollection();
    $collection->add('test_route.get', new Route('/test', [], [], [], '', [], ['GET']));

    $this->expectException(MethodNotAllowedException::class);

    $method_filter = new MethodFilter();
    $method_filter->filter($collection, $request);
  }

  /**
   * Tests method not allowed exception with multiple routes.
   *
   * @legacy-covers ::filter
   */
  public function testMethodNotAllowedExceptionWithMultipleRoutes(): void {
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
   * Tests filtered methods.
   */
  public function testFilteredMethods(): void {
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
   * @legacy-covers ::filter
   */
  public function testCollectionOrder(): void {
    $request = Request::create('/test', 'GET');

    $collection = new RouteCollection();
    $collection->add('entity.taxonomy_term.canonical', new Route('/test'));
    $collection->add('views.view.taxonomy_term_page', new Route('/test', [], [], [], '', [], ['GET', 'POST']));

    $method_filter = new MethodFilter();
    $result_collection = $method_filter->filter($collection, $request);

    $this->assertEquals(['entity.taxonomy_term.canonical', 'views.view.taxonomy_term_page'], array_keys($result_collection->all()));
  }

}
