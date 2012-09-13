<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\PartialMatcherTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Routing\PathMatcher;
use Drupal\Core\Database\Database;
use Drupal\Core\Routing\MatcherDumper;

use Exception;

/**
 * Basic tests for the UrlMatcherDumper.
 */
class PathMatcherTest extends UnitTestBase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  public static function getInfo() {
    return array(
      'name' => 'Path matcher tests',
      'description' => 'Confirm that the path matching library is working correctly.',
      'group' => 'Routing',
    );
  }

  function __construct($test_id = NULL) {
    parent::__construct($test_id);

    $this->fixtures = new RoutingFixtures();
  }

  public function tearDown() {
    $this->fixtures->dropTables(Database::getConnection());

   parent::tearDown();
  }

  /**
   * Confirms that the correct candidate outlines are generated.
   */
  public function testCandidateOutlines() {

    $connection = Database::getConnection();
    $matcher = new PathMatcher($connection);

    $parts = array('node', '5', 'edit');

    $candidates = $matcher->getCandidateOutlines($parts);

    //debug($candidates);

    $candidates = array_flip($candidates);

    $this->assertTrue(count($candidates) == 4, 'Correct number of candidates found');
    $this->assertTrue(array_key_exists('/node/5/edit', $candidates), 'First candidate found.');
    $this->assertTrue(array_key_exists('/node/5/%', $candidates), 'Second candidate found.');
    $this->assertTrue(array_key_exists('/node/%/edit', $candidates), 'Third candidate found.');
    $this->assertTrue(array_key_exists('/node/%/%', $candidates), 'Fourth candidate found.');
  }

  /**
   * Confirms that we can find routes with the exact incoming path.
   */
  function testExactPathMatch() {
    $connection = Database::getConnection();
    $matcher = new PathMatcher($connection, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, 'test_routes');
    $dumper->addRoutes($this->fixtures->sampleRouteCollection());
    $dumper->dump();

    $path = '/path/one';

    $request = Request::create($path, 'GET');

    $routes = $matcher->matchRequestPartial($request);

    foreach ($routes as $route) {
      $this->assertEqual($route->getPattern(), $path, 'Found path has correct pattern');
    }
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  function testOutlinePathMatch() {
    $connection = Database::getConnection();
    $matcher = new PathMatcher($connection, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, 'test_routes');
    $dumper->addRoutes($this->fixtures->complexRouteCollection());
    $dumper->dump();

    $path = '/path/1/one';

    $request = Request::create($path, 'GET');

    $routes = $matcher->matchRequestPartial($request);

    // All of the matching paths have the correct pattern.
    foreach ($routes as $route) {
      $this->assertEqual($route->compile()->getPatternOutline(), '/path/%/one', 'Found path has correct pattern');
    }

    $this->assertEqual(count($routes->all()), 2, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_a'), 'The first matching route was found.');
    $this->assertNotNull($routes->get('route_b'), 'The second matching route was not found.');
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  function testOutlinePathMatchDefaults() {
    $connection = Database::getConnection();
    $matcher = new PathMatcher($connection, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/path/{value}', array(
      'value' => 'poink',
    )));

    $dumper = new MatcherDumper($connection, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/path';

    $request = Request::create($path, 'GET');

    try {
      $routes = $matcher->matchRequestPartial($request);

      // All of the matching paths have the correct pattern.
      foreach ($routes as $route) {
        $compiled = $route->compile();
        $this->assertEqual($route->compile()->getPatternOutline(), '/some/path', 'Found path has correct pattern');
      }

      $this->assertEqual(count($routes->all()), 1, 'The correct number of routes was found.');
      $this->assertNotNull($routes->get('poink'), 'The first matching route was found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matching route found with default argument value.');
    }
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  function testOutlinePathMatchDefaultsCollision() {
    $connection = Database::getConnection();
    $matcher = new PathMatcher($connection, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/path/{value}', array(
      'value' => 'poink',
    )));
    $collection->add('narf', new Route('/some/path/here'));

    $dumper = new MatcherDumper($connection, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/path';

    $request = Request::create($path, 'GET');

    try {
      $routes = $matcher->matchRequestPartial($request);

      // All of the matching paths have the correct pattern.
      foreach ($routes as $route) {
        $compiled = $route->compile();
        $this->assertEqual($route->compile()->getPatternOutline(), '/some/path', 'Found path has correct pattern');
      }

      $this->assertEqual(count($routes->all()), 1, 'The correct number of routes was found.');
      $this->assertNotNull($routes->get('poink'), 'The first matching route was found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matching route found with default argument value.');
    }
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  function testOutlinePathMatchDefaultsCollision2() {
    $connection = Database::getConnection();
    $matcher = new PathMatcher($connection, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/path/{value}', array(
      'value' => 'poink',
    )));
    $collection->add('narf', new Route('/some/path/here'));

    $dumper = new MatcherDumper($connection, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/path/here';

    $request = Request::create($path, 'GET');

    try {
      $routes = $matcher->matchRequestPartial($request);

      // All of the matching paths have the correct pattern.
      foreach ($routes as $route) {
        $this->assertEqual($route->compile()->getPatternOutline(), '/some/path/here', 'Found path has correct pattern');
      }

      $this->assertEqual(count($routes->all()), 1, 'The correct number of routes was found.');
      $this->assertNotNull($routes->get('narf'), 'The first matching route was found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matching route found with default argument value.');
    }
  }

  /**
   * Confirm that an exception is thrown when no matching path is found.
   */
  function testOutlinePathNoMatch() {
    $connection = Database::getConnection();
    $matcher = new PathMatcher($connection, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, 'test_routes');
    $dumper->addRoutes($this->fixtures->complexRouteCollection());
    $dumper->dump();

    $path = '/no/such/path';

    $request = Request::create($path, 'GET');

    try {
      $routes = $matcher->matchRequestPartial($request);
      $this->fail(t('No exception was thrown.'));
    }
    catch (Exception $e) {
      $this->assertTrue($e instanceof ResourceNotFoundException, 'The correct exception was thrown.');
    }

  }

}
