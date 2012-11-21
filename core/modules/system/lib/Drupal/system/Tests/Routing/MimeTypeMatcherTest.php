<?php

/**
 * @file
 * Contains Drupal\system\Tests\Routing\MimeTypeMatcherTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Routing\MimeTypeMatcher;
use Drupal\Core\Routing\NestedMatcher;
use Drupal\Core\Routing\FirstEntryFinalMatcher;

use Exception;

/**
 * Basic tests for the MimeTypeMatcher class.
 */
class MimeTypeMatcherTest extends UnitTestBase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  public static function getInfo() {
    return array(
      'name' => 'Partial matcher MIME types tests',
      'description' => 'Confirm that the mime types partial matcher is functioning properly.',
      'group' => 'Routing',
    );
  }

  function __construct($test_id = NULL) {
    parent::__construct($test_id);

    $this->fixtures = new RoutingFixtures();
  }

  /**
   * Confirms that the MimeType matcher matches properly.
   */
  public function testFilterRoutes() {

    $matcher = new MimeTypeMatcher();
    $matcher->setCollection($this->fixtures->sampleRouteCollection());

    // Tests basic JSON request.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
    $routes = $matcher->matchRequestPartial($request);
    $this->assertEqual(count($routes->all()), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_c'), 'The json route was found.');
    $this->assertNull($routes->get('route_e'), 'The html route was not found.');

    // Tests JSON request with alternative JSON MIME type Accept header.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/x-json, text/xml;q=0.9');
    $routes = $matcher->matchRequestPartial($request);
    $this->assertEqual(count($routes->all()), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_c'), 'The json route was found.');
    $this->assertNull($routes->get('route_e'), 'The html route was not found.');

    // Tests basic HTML request.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'text/html, text/xml;q=0.9');
    $routes = $matcher->matchRequestPartial($request);
    $this->assertEqual(count($routes->all()), 4, 'The correct number of routes was found.');
    $this->assertNull($routes->get('route_c'), 'The json route was not found.');
    $this->assertNotNull($routes->get('route_e'), 'The html route was found.');
  }

  /**
   * Confirms we can nest multiple partial matchers.
   */
  public function testNestedMatcher() {

    $matcher = new NestedMatcher();

    $matcher->setInitialMatcher(new MockPathMatcher($this->fixtures->sampleRouteCollection()));
    $matcher->addPartialMatcher(new MimeTypeMatcher());
    $matcher->setFinalMatcher(new FirstEntryFinalMatcher());

    $request = Request::create('/path/two', 'GET');
    $request->headers->set('Accept', 'text/html, text/xml;q=0.9');

    $attributes = $matcher->matchRequest($request);
    $this->assertEqual($attributes['_route'], 'route_e', 'The correct matching route was found.');
  }

  /**
   * Confirms that the MimeTypeMatcher matcher throws an exception for no-route.
   */
  public function testNoRouteFound() {
    $matcher = new MimeTypeMatcher();

    // Remove the sample routes that would match any method.
    $routes = $this->fixtures->sampleRouteCollection();
    $routes->remove('route_a');
    $routes->remove('route_b');
    $routes->remove('route_c');
    $routes->remove('route_d');

    $matcher->setCollection($routes);

    try {
      $request = Request::create('path/two', 'GET');
      $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
      $routes = $matcher->matchRequestPartial($request);
      $this->fail(t('No exception was thrown.'));
    }
    catch (Exception $e) {
      $this->assertTrue($e instanceof RouteNotFoundException, 'The correct exception was thrown.');
    }
  }

}
