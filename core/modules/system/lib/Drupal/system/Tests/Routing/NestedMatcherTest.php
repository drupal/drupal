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
class NestedMatcherTest extends UnitTestBase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  public static function getInfo() {
    return array(
      'name' => 'NestedMatcher tests',
      'description' => 'Confirm that the NestedMatcher system is working properly.',
      'group' => 'Routing',
    );
  }

  function __construct($test_id = NULL) {
    parent::__construct($test_id);

    $this->fixtures = new RoutingFixtures();
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

    $this->assertEqual($attributes['_route'], 'route_a', 'The correct matching route was found.');
  }
}
