<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\ContentTypeHeaderMatcherTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\ContentTypeHeaderMatcher;
use Drupal\Tests\Core\Routing\RoutingFixtures;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Confirm that the content types partial matcher is functioning properly.
 *
 * @group Routing
 */
class ContentTypeHeaderMatcherTest extends UnitTestCase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  /**
   * The matcher object that is going to be tested.
   *
   * @var \Drupal\Core\Routing\ContentTypeHeaderMatcher
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fixtures = new RoutingFixtures();
    $this->matcher = new ContentTypeHeaderMatcher();
  }

  /**
   * Tests that routes are not filtered on GET requests.
   */
  public function testGetRequestFilter() {
    $collection = $this->fixtures->sampleRouteCollection();
    $collection->addCollection($this->fixtures->contentRouteCollection());

    $request = Request::create('path/two', 'GET');
    $routes = $this->matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 7, 'The correct number of routes was found.');
  }

  /**
   * Tests that XML-restricted routes get filtered out on JSON requests.
   */
  public function testJsonRequest() {
    $collection = $this->fixtures->sampleRouteCollection();
    $collection->addCollection($this->fixtures->contentRouteCollection());

    $request = Request::create('path/two', 'POST');
    $request->headers->set('Content-type', 'application/json');
    $routes = $this->matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 6, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_f'), 'The json route was found.');
    $this->assertNull($routes->get('route_g'), 'The xml route was not found.');
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, 'route_f', 'The json route is the first one in the collection.');
      break;
    }
  }

  /**
   * Tests route filtering on POST form submission requests.
   */
  public function testPostForm() {
    $collection = $this->fixtures->sampleRouteCollection();
    $collection->addCollection($this->fixtures->contentRouteCollection());

    // Test that all XML and JSON restricted routes get filtered out on a POST
    // form submission.
    $request = Request::create('path/two', 'POST');
    $request->headers->set('Content-type', 'application/www-form-urlencoded');
    $routes = $this->matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 5, 'The correct number of routes was found.');
    $this->assertNull($routes->get('route_f'), 'The json route was found.');
    $this->assertNull($routes->get('route_g'), 'The xml route was not found.');
  }

  /**
   * Confirms that the matcher throws an exception for no-route.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException
   * @expectedExceptionMessage No route found that matches the Content-Type header.
   */
  public function testNoRouteFound() {
    $matcher = new ContentTypeHeaderMatcher();

    $routes = $this->fixtures->contentRouteCollection();
    $request = Request::create('path/two', 'POST');
    $request->headers->set('Content-type', 'application/hal+json');
    $matcher->filter($routes, $request);
    $this->fail('No exception was thrown.');
  }

}
