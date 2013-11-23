<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\MimeTypeMatcher;
use Drupal\Tests\UnitTestCase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Basic tests for the MimeTypeMatcher class.
 *
 * @group Drupal
 * @group Routing
 */
class MimeTypeMatcherTest extends UnitTestCase {

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

  public function setUp() {
    $this->fixtures = new RoutingFixtures();
  }

  /**
   * Confirms that the MimeType matcher matches properly.
   *
   * @param string $accept_header
   *   The 'Accept` header to test.
   * @param integer $routes_count
   *   The number of expected routes.
   * @param string $null_route
   *   The route that is expected to be null.
   * @param string $not_null_route
   *   The route that is expected to not be null.
   *
   * @dataProvider providerTestFilterRoutes
   */
  public function testFilterRoutes($accept_header, $routes_count, $null_route, $not_null_route) {

    $matcher = new MimeTypeMatcher();
    $collection = $this->fixtures->sampleRouteCollection();

    // Tests basic JSON request.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', $accept_header);
    $routes = $matcher->filter($collection, $request);
    $this->assertEquals($routes_count, count($routes), 'An incorrect number of routes was found.');
    $this->assertNull($routes->get($null_route), 'A route was found where it should be null.');
    $this->assertNotNull($routes->get($not_null_route), 'The expected route was not found.');
  }

  /**
   * Provides test routes for testFilterRoutes.
   *
   * @return array
   *   An array of arrays, each containing the parameters necessary for the
   *   testFilterRoutes method.
   */
  public function providerTestFilterRoutes() {
    return array(
      // Tests basic JSON request.
      array('application/json, text/xml;q=0.9', 4, 'route_e', 'route_c'),

      // Tests JSON request with alternative JSON MIME type Accept header.
      array('application/x-json, text/xml;q=0.9', 4, 'route_e', 'route_c'),

      // Tests basic HTML request.
      array('text/html, text/xml;q=0.9', 4, 'route_c', 'route_e'),
    );
  }

  /**
   * Confirms that the MimeTypeMatcher matcher throws an exception for no-route.
   *
   * @expectedException Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
   */
  public function testNoRouteFound() {
    $matcher = new MimeTypeMatcher();

    // Remove the sample routes that would match any method.
    $routes = $this->fixtures->sampleRouteCollection();
    $routes->remove('route_a');
    $routes->remove('route_b');
    $routes->remove('route_c');
    $routes->remove('route_d');

    // This should throw NotAcceptableHttpException.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
    $routes = $matcher->filter($routes, $request);
  }

}
