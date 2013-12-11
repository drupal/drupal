<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\LazyLoadingRouteCollectionTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\LazyLoadingRouteCollection;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests the lazy loaded route collection.
 *
 * @group \Drupal
 *
 * @see \Drupal\Core\Routing\LazyLoadingRouteCollection
 */
class LazyLoadingRouteCollectionTest extends UnitTestCase {

  /**
   * Stores all the routes used in the test.
   *
   * @var array
   */
  protected $routes = array();

  /**
   * The tested route collection.
   *
   * @var \Drupal\Core\Routing\LazyLoadingRouteCollection
   */
  protected $routeCollection;

  public static function getInfo() {
    return array(
      'name' => 'Lazy loaded route collection',
      'description' => 'Tests the lazy loaded route collection.',
      'group' => 'Routing',
    );
  }

  protected function setUp() {
    for ($i = 0; $i < 20; $i++) {
      $this->routes['test_route_' . $i] = new Route('/test-route-' . $i);
    }

    $this->routeCollection = new TestRouteCollection($this->routes);
  }

  /**
   * Tests iterating the lazy loading route collection.
   *
   * @see \Drupal\Core\Routing\LazyLoadingRouteCollection::current()
   * @see \Drupal\Core\Routing\LazyLoadingRouteCollection::key()
   * @see \Drupal\Core\Routing\LazyLoadingRouteCollection::rewind()
   */
  public function testIterating() {
    // Execute the foreach loop twice to ensure that rewind is called.
    for ($i = 0; $i < 2; $i++) {
      $route_names = array_keys($this->routes);
      $count = 0;
      foreach ($this->routeCollection as $route_name => $route) {
        $this->assertEquals($route_names[$count], $route_name);
        $this->assertEquals($this->routes[$route_names[$count]], $route);

        $count++;
      }
    }
  }

}

/**
 * Wrapper class to "inject" loaded routes.
 */
class TestRouteCollection extends LazyLoadingRouteCollection {

  /**
   * {@inheritdoc}
   */
  const ROUTE_LOADED_PER_TIME = 2;

  /**
   * Stores all elements.
   *
   * @var \Symfony\Component\Routing\Route[]
   */
  protected $allRoutes;

  /**
   * Creates a TestCollection instance.
   *
   * @param \Symfony\Component\Routing\Route[] $all_routes
   *   Contains all the routes used in the test.
   */
  public function __construct(array $all_routes) {
    $this->allRoutes = $all_routes;
    $this->loadNextElements($this->currentRoute);
  }

  /**
   * {@inheritdoc}
   */
  protected function loadNextElements($offset) {
    $elements = array_slice($this->allRoutes, $offset, static::ROUTE_LOADED_PER_TIME);

    $this->elements = $elements;
  }

}
