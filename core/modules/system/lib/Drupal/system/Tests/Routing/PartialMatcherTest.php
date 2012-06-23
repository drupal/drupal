<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\PartialMatcherTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Routing\HttpMethodMatcher;
use Drupal\Core\Routing\NestedMatcher;

/**
 * Basic tests for the UrlMatcherDumper.
 */
class PartialMatcherTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Partial matcher HTTP Method tests',
      'description' => 'Confirm that the Http Method partial matcher is functioning properly.',
      'group' => 'Routing',
    );
  }

  public function setUp() {
    parent::setUp();
  }

  /**
   * Confirms that the HttpMethod matcher matches properly.
   */
  public function testFilterRoutes() {

    $matcher = new HttpMethodMatcher();
    $matcher->setCollection($this->sampleRouteCollection());

    $routes = $matcher->matchRequestPartial(Request::create('path/one', 'GET'));

    $this->assertEqual(count($routes->all()), 4, t('The correct number of routes was found.'));
    $this->assertNotNull($routes->get('route_a'), t('The first matching route was found.'));
    $this->assertNull($routes->get('route_b'), t('The non-matching route was not found.'));
    $this->assertNotNull($routes->get('route_c'), t('The second matching route was found.'));
    $this->assertNotNull($routes->get('route_d'), t('The all-matching route was found.'));
    $this->assertNotNull($routes->get('route_e'), t('The multi-matching route was found.'));
  }

  /**
   * Confirms we can nest multiple partial matchers.
   */
  public function testNestedMatcher() {

    $matcher = new NestedMatcher();

    $matcher->setInitialMatcher(new MockPathMatcher($this->sampleRouteCollection()));
    $matcher->addPartialMatcher(new HttpMethodMatcher());
    $matcher->setFinalMatcher(new MockFinalMatcher());

    $request = Request::create('/path/one', 'GET');

    $attributes = $matcher->matchRequest($request);

    $this->assertEqual($attributes['_route'], 'route_a', t('The correct matching route was found.'));
  }

  /**
   * Returns a standard set of routes for testing.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   */
  protected function sampleRouteCollection() {
    $collection = new RouteCollection();

    $route = new Route('path/one');
    $route->setRequirement('_method', 'GET');
    $collection->add('route_a', $route);

    $route = new Route('path/one');
    $route->setRequirement('_method', 'PUT');
    $collection->add('route_b', $route);

    $route = new Route('path/two');
    $route->setRequirement('_method', 'GET');
    $collection->add('route_c', $route);

    $route = new Route('path/three');
    $collection->add('route_d', $route);

    $route = new Route('path/two');
    $route->setRequirement('_method', 'GET|HEAD');
    $collection->add('route_e', $route);

    return $collection;
  }
}

