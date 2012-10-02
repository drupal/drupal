<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\NestedMatcherTest.
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
 * Basic tests for the NestedMatcher class.
 */
class FirstEntryFinalMatcherTest extends UnitTestBase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  public static function getInfo() {
    return array(
      'name' => 'FirstEntryFinalMatcher tests',
      'description' => 'Confirm that the FirstEntryFinalMatcher is working properly.',
      'group' => 'Routing',
    );
  }

  function __construct($test_id = NULL) {
    parent::__construct($test_id);

    $this->fixtures = new RoutingFixtures();
  }

  /**
   * Confirms the final matcher returns correct attributes for static paths.
   */
  public function testFinalMatcherStatic() {

    $collection = new RouteCollection();
    $collection->add('route_a', new Route('/path/one', array(
      '_controller' => 'foo',
    )));

    $request = Request::create('/path/one', 'GET');

    $matcher = new FirstEntryFinalMatcher();
    $matcher->setCollection($collection);
    $attributes = $matcher->matchRequest($request);

    $this->assertEqual($attributes['_route'], 'route_a', 'The correct matching route was found.');
    $this->assertEqual($attributes['_controller'], 'foo', 'The correct controller was found.');
  }

  /**
   * Confirms the final matcher returns correct attributes for pattern paths.
   */
  public function testFinalMatcherPattern() {

    $collection = new RouteCollection();
    $collection->add('route_a', new Route('/path/one/{value}', array(
      '_controller' => 'foo',
    )));

    $request = Request::create('/path/one/narf', 'GET');
    $request->attributes->set('system_path', 'path/one/narf');

    $matcher = new FirstEntryFinalMatcher();
    $matcher->setCollection($collection);
    $attributes = $matcher->matchRequest($request);

    $this->assertEqual($attributes['_route'], 'route_a', 'The correct matching route was found.');
    $this->assertEqual($attributes['_controller'], 'foo', 'The correct controller was found.');
    $this->assertEqual($attributes['value'], 'narf', 'Required placeholder value found.');
  }

  /**
   * Confirms the final matcher returns correct attributes with default values.
   */
  public function testFinalMatcherPatternDefalts() {

    $collection = new RouteCollection();
    $collection->add('route_a', new Route('/path/one/{value}', array(
      '_controller' => 'foo',
      'value' => 'poink'
    )));

    $request = Request::create('/path/one', 'GET');
    $request->attributes->set('system_path', 'path/one');

    $matcher = new FirstEntryFinalMatcher();
    $matcher->setCollection($collection);
    $attributes = $matcher->matchRequest($request);

    $this->assertEqual($attributes['_route'], 'route_a', 'The correct matching route was found.');
    $this->assertEqual($attributes['_controller'], 'foo', 'The correct controller was found.');
    $this->assertEqual($attributes['value'], 'poink', 'Optional placeholder value used default.');
  }
}
