<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\HttpMethodMMatcherTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Routing\HttpMethodMatcher;
use Drupal\Core\Routing\NestedMatcher;
use Drupal\Core\Routing\FirstEntryFinalMatcher;

use Exception;

/**
 * Basic tests for the HttpMethodMatcher class.
 */
class HttpMethodMatcherTest extends UnitTestBase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  public static function getInfo() {
    return array(
      'name' => 'Partial matcher HTTP Method tests',
      'description' => 'Confirm that the Http Method partial matcher is functioning properly.',
      'group' => 'Routing',
    );
  }

  function __construct($test_id = NULL) {
    parent::__construct($test_id);

    $this->fixtures = new RoutingFixtures();
  }

  /**
   * Confirms that the HttpMethod matcher matches properly.
   */
  public function testFilterRoutes() {

    $matcher = new HttpMethodMatcher();
    $matcher->setCollection($this->fixtures->sampleRouteCollection());

    $routes = $matcher->matchRequestPartial(Request::create('path/one', 'GET'));

    $this->assertEqual(count($routes->all()), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_a'), 'The first matching route was found.');
    $this->assertNull($routes->get('route_b'), 'The non-matching route was not found.');
    $this->assertNotNull($routes->get('route_c'), 'The second matching route was found.');
    $this->assertNotNull($routes->get('route_d'), 'The all-matching route was found.');
    $this->assertNotNull($routes->get('route_e'), 'The multi-matching route was found.');
  }

  /**
   * Confirms we can nest multiple partial matchers.
   */
  public function testNestedMatcher() {

    $matcher = new NestedMatcher();

    $matcher->setInitialMatcher(new MockPathMatcher($this->fixtures->sampleRouteCollection()));
    $matcher->addPartialMatcher(new HttpMethodMatcher());
    $matcher->setFinalMatcher(new FirstEntryFinalMatcher());

    $request = Request::create('/path/one', 'GET');

    $attributes = $matcher->matchRequest($request);

    $this->assertEqual($attributes['_route']->getOption('_name'), 'route_a', 'The correct matching route was found.');
  }

  /**
   * Confirms that the HttpMethod matcher throws an exception for no-route.
   */
  public function testNoRouteFound() {
    $matcher = new HttpMethodMatcher();

    // Remove the sample route that would match any method.
    $routes = $this->fixtures->sampleRouteCollection();
    $routes->remove('route_d');

    $matcher->setCollection($routes);

    try {
      $routes = $matcher->matchRequestPartial(Request::create('path/one', 'DELETE'));
      $this->fail(t('No exception was thrown.'));
    }
    catch (Exception $e) {
      $this->assertTrue($e instanceof MethodNotAllowedException, 'The correct exception was thrown.');
    }

  }
}
