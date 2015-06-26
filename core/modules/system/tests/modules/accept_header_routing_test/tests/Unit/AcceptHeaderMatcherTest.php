<?php

/**
 * @file
 * Contains \Drupal\Tests\accept_header_routing_teste\Unit\Routing\AcceptHeaderMatcherTest.
 */

namespace Drupal\Tests\accept_header_routing_teste\Unit\Routing;

use Drupal\accept_header_routing_test\Routing\AcceptHeaderMatcher;
use Drupal\Tests\Core\Routing\RoutingFixtures;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Confirm that the mime types partial matcher is functioning properly.
 *
 * @group Routing
 */
class AcceptHeaderMatcherTest extends UnitTestCase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var \Drupal\Tests\Core\Routing\RoutingFixtures
   */
  protected $fixtures;

  /**
   * The matcher object that is going to be tested.
   *
   * @var \Drupal\accept_header_routing_test\Routing\AcceptHeaderMatcher
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fixtures = new RoutingFixtures();
    $this->matcher = new AcceptHeaderMatcher();
  }

  /**
   * Provides data for the Accept header filtering test.
   *
   * @see Drupal\Tests\Core\Routing\AcceptHeaderMatcherTest::testAcceptFiltering()
   */
  public function acceptFilterProvider() {
    return [
      // Check that JSON routes get filtered and prioritized correctly.
      ['application/json, text/xml;q=0.9', 'json', 'route_c', 'route_e'],
      // Tests a JSON request with alternative JSON MIME type Accept header.
      ['application/x-json, text/xml;q=0.9', 'json', 'route_c', 'route_e'],
      // Tests a standard HTML request.
      ['text/html, text/xml;q=0.9', 'html', 'route_e', 'route_c'],
    ];
  }

  /**
   * Tests that requests using Accept headers get filtered correctly.
   *
   * @param string $accept_header
   *   The HTTP Accept header value of the request.
   * @param string $format
   *   The request format.
   * @param string $included_route
   *   The route name that should survive the filter and be ranked first.
   * @param string $excluded_route
   *   The route name that should be filtered out during matching.
   *
   * @dataProvider acceptFilterProvider
   */
  public function testAcceptFiltering($accept_header, $format, $included_route, $excluded_route) {
    $collection = $this->fixtures->sampleRouteCollection();

    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', $accept_header);
    $request->setRequestFormat($format);
    $routes = $this->matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get($included_route), "Route $included_route was found when matching $accept_header.");
    $this->assertNull($routes->get($excluded_route), "Route $excluded_route was not found when matching $accept_header.");
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, $included_route, "Route $included_route is the first one in the collection when matching $accept_header.");
      break;
    }
  }

  /**
   * Confirms that the AcceptHeaderMatcher throws an exception for no-route.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
   * @expectedExceptionMessage No route found for the specified formats application/json text/xml.
   */
  public function testNoRouteFound() {
    // Remove the sample routes that would match any method.
    $routes = $this->fixtures->sampleRouteCollection();
    $routes->remove('route_a');
    $routes->remove('route_b');
    $routes->remove('route_c');
    $routes->remove('route_d');

    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
    $request->setRequestFormat('json');
    $this->matcher->filter($routes, $request);
    $this->matcher->filter($routes, $request);
    $this->fail('No exception was thrown.');
  }

}
