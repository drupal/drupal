<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\ContentTypeHeaderMatcher;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Confirm that the content types partial matcher is functioning properly.
 *
 * @group Routing
 *
 * @coversDefaultClass \Drupal\Core\Routing\ContentTypeHeaderMatcher
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
  protected function setUp(): void {
    parent::setUp();

    $this->fixtures = new RoutingFixtures();
    $this->matcher = new ContentTypeHeaderMatcher();
  }

  /**
   * Tests that routes are not filtered on safe requests.
   *
   * @dataProvider providerTestSafeRequestFilter
   */
  public function testSafeRequestFilter($method) {
    $collection = $this->fixtures->sampleRouteCollection();
    $collection->addCollection($this->fixtures->contentRouteCollection());

    $request = Request::create('path/two', $method);
    $routes = $this->matcher->filter($collection, $request);
    $this->assertCount(7, $routes, 'The correct number of routes was found.');
  }

  public function providerTestSafeRequestFilter() {
    return [
      ['GET'],
      ['HEAD'],
      ['OPTIONS'],
      ['TRACE'],
      ['DELETE'],
    ];
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
    $this->assertCount(6, $routes, 'The correct number of routes was found.');
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
    $this->assertCount(5, $routes, 'The correct number of routes was found.');
    $this->assertNull($routes->get('route_f'), 'The json route was found.');
    $this->assertNull($routes->get('route_g'), 'The xml route was not found.');
  }

  /**
   * Confirms that the matcher throws an exception for no-route.
   *
   * @covers ::filter
   */
  public function testNoRouteFound() {
    $matcher = new ContentTypeHeaderMatcher();

    $routes = $this->fixtures->contentRouteCollection();
    $request = Request::create('path/two', 'POST');
    $request->headers->set('Content-type', 'application/hal+json');
    $this->expectException(UnsupportedMediaTypeHttpException::class);
    $this->expectExceptionMessage('No route found that matches "Content-Type: application/hal+json"');
    $matcher->filter($routes, $request);
  }

  /**
   * Confirms that the matcher throws an exception for missing request header.
   *
   * @covers ::filter
   */
  public function testContentTypeRequestHeaderMissing() {
    $matcher = new ContentTypeHeaderMatcher();

    $routes = $this->fixtures->contentRouteCollection();
    $request = Request::create('path/two', 'POST');
    // Delete all request headers that Request::create() sets by default.
    $request->headers = new ParameterBag();
    $this->expectException(UnsupportedMediaTypeHttpException::class);
    $this->expectExceptionMessage('No "Content-Type" request header specified');
    $matcher->filter($routes, $request);
  }

}
