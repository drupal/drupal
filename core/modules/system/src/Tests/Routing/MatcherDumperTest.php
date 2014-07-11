<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Routing\MatcherDumperTest.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\State\State;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Routing\MatcherDumper;
use Drupal\Tests\Core\Routing\RoutingFixtures;

/**
 * Confirm that the matcher dumper is functioning properly.
 *
 * @group Routing
 */
class MatcherDumperTest extends UnitTestBase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  function __construct($test_id = NULL) {
    parent::__construct($test_id);

    $this->fixtures = new RoutingFixtures();
    $this->state = new State(new KeyValueMemoryFactory());
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Confirms that the dumper can be instantiated successfully.
   */
  function testCreate() {
    $connection = Database::getConnection();
    $dumper= new MatcherDumper($connection, $this->state);

    $class_name = 'Drupal\Core\Routing\MatcherDumper';
    $this->assertTrue($dumper instanceof $class_name, 'Dumper created successfully');
  }

  /**
   * Confirms that we can add routes to the dumper.
   */
  function testAddRoutes() {
    $connection = Database::getConnection();
    $dumper= new MatcherDumper($connection, $this->state);

    $route = new Route('test');
    $collection = new RouteCollection();
    $collection->add('test_route', $route);

    $dumper->addRoutes($collection);

    $dumper_routes = $dumper->getRoutes()->all();
    $collection_routes = $collection->all();

    foreach ($dumper_routes as $name => $route) {
      $this->assertEqual($route->getPath(), $collection_routes[$name]->getPath(), 'Routes match');
    }
  }

  /**
   * Confirms that we can add routes to the dumper when it already has some.
   */
  function testAddAdditionalRoutes() {
    $connection = Database::getConnection();
    $dumper= new MatcherDumper($connection, $this->state);

    $route = new Route('test');
    $collection = new RouteCollection();
    $collection->add('test_route', $route);
    $dumper->addRoutes($collection);

    $route = new Route('test2');
    $collection2 = new RouteCollection();
    $collection2->add('test_route2', $route);
    $dumper->addRoutes($collection2);

    // Merge the two collections together so we can test them.
    $collection->addCollection(clone $collection2);

    $dumper_routes = $dumper->getRoutes()->all();
    $collection_routes = $collection->all();

    $success = TRUE;
    foreach ($collection_routes as $name => $route) {
      if (empty($dumper_routes[$name])) {
        $success = FALSE;
        $this->fail(t('Not all routes found in the dumper.'));
      }
    }

    if ($success) {
      $this->pass('All routes found in the dumper.');
    }
  }

  /**
   * Confirm that we can dump a route collection to the database.
   */
  public function testDump() {
    $connection = Database::getConnection();
    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');

    $route = new Route('/test/{my}/path');
    $route->setOption('compiler_class', 'Drupal\Core\Routing\RouteCompiler');
    $collection = new RouteCollection();
    $collection->add('test_route', $route);

    $dumper->addRoutes($collection);

    $this->fixtures->createTables($connection);

    $dumper->dump(array('provider' => 'test'));

    $record = $connection->query("SELECT * FROM {test_routes} WHERE name= :name", array(':name' => 'test_route'))->fetchObject();

    $loaded_route = unserialize($record->route);

    $this->assertEqual($record->name, 'test_route', 'Dumped route has correct name.');
    $this->assertEqual($record->path, '/test/{my}/path', 'Dumped route has correct pattern.');
    $this->assertEqual($record->pattern_outline, '/test/%/path', 'Dumped route has correct pattern outline.');
    $this->assertEqual($record->fit, 5 /* 101 in binary */, 'Dumped route has correct fit.');
    $this->assertTrue($loaded_route instanceof Route, 'Route object retrieved successfully.');
  }

  /**
   * Tests the determination of the masks generation.
   */
  public function testMenuMasksGeneration() {
    $connection = Database::getConnection();
    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');

    $collection = new RouteCollection();
    $collection->add('test_route_1', new Route('/test-length-3/{my}/path'));
    $collection->add('test_route_2', new Route('/test-length-3/hello/path'));
    $collection->add('test_route_3', new Route('/test-length-5/{my}/path/marvin/magrathea'));
    $collection->add('test_route_4', new Route('/test-length-7/{my}/path/marvin/magrathea/earth/ursa-minor'));

    $dumper->addRoutes($collection);

    $this->fixtures->createTables($connection);

    $dumper->dump(array('provider' => 'test'));
    // Using binary for readability, we expect a 0 at any wildcard slug. They
    // should be ordered from longest to shortest.
    $expected = array(
      bindec('1011111'),
      bindec('10111'),
      bindec('111'),
      bindec('101'),
    );
    $this->assertEqual($this->state->get('routing.menu_masks.test_routes'), $expected);
  }

}
