<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Routing\MatcherDumper;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\State\State;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Core\Routing\RoutingFixtures;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Confirm that the matcher dumper is functioning properly.
 *
 * @group Routing
 */
class MatcherDumperTest extends KernelTestBase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var \Drupal\Tests\Core\Routing\RoutingFixtures
   */
  protected $fixtures;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger.
   */
  protected TestLogger $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fixtures = new RoutingFixtures();
    $time = $this->prophesize(TimeInterface::class)->reveal();
    $this->state = new State(new KeyValueMemoryFactory(), new MemoryBackend($time), new NullLockBackend());
    $this->logger = new TestLogger();
  }

  /**
   * Confirms that the dumper can be instantiated successfully.
   */
  public function testCreate(): void {
    $connection = Database::getConnection();
    $dumper = new MatcherDumper($connection, $this->state, $this->logger);

    $class_name = 'Drupal\Core\Routing\MatcherDumper';
    $this->assertInstanceOf($class_name, $dumper);
  }

  /**
   * Confirms that we can add routes to the dumper.
   */
  public function testAddRoutes(): void {
    $connection = Database::getConnection();
    $dumper = new MatcherDumper($connection, $this->state, $this->logger);

    $route = new Route('test');
    $collection = new RouteCollection();
    $collection->add('test_route', $route);

    $dumper->addRoutes($collection);

    $dumper_routes = $dumper->getRoutes()->all();
    $collection_routes = $collection->all();

    foreach ($dumper_routes as $name => $route) {
      $this->assertEquals($collection_routes[$name]->getPath(), $route->getPath(), 'Routes match');
    }
  }

  /**
   * Confirms that we can add routes to the dumper when it already has some.
   */
  public function testAddAdditionalRoutes(): void {
    $connection = Database::getConnection();
    $dumper = new MatcherDumper($connection, $this->state, $this->logger);

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

    foreach ($collection_routes as $name => $route) {
      $this->assertNotEmpty($dumper_routes[$name], "Route $name should be present in the dumper.");
    }
  }

  /**
   * Confirm that we can dump a route collection to the database.
   */
  public function testDump(): void {
    $connection = Database::getConnection();
    $dumper = new MatcherDumper($connection, $this->state, $this->logger, 'test_routes');

    $route = new Route('/test/{my}/path');
    $route->setOption('compiler_class', RouteCompiler::class);
    $collection = new RouteCollection();
    $collection->add('test_route', $route);

    $dumper->addRoutes($collection);

    $this->fixtures->createTables($connection);

    $dumper->dump(['provider' => 'test']);

    $record = $connection->select('test_routes', 'tr')
      ->fields('tr')
      ->condition('name', 'test_route')
      ->execute()
      ->fetchObject();

    $loaded_route = unserialize($record->route);

    $this->assertEquals('test_route', $record->name, 'Dumped route has correct name.');
    $this->assertEquals('/test/{my}/path', $record->path, 'Dumped route has correct pattern.');
    $this->assertEquals('/test/%/path', $record->pattern_outline, 'Dumped route has correct pattern outline.');
    // Verify that the dumped route has the correct fit. Note that 5 decimal
    // equals 101 binary.
    $this->assertEquals(5, $record->fit, 'Dumped route has correct fit.');
    $this->assertInstanceOf(Route::class, $loaded_route);
  }

  /**
   * Tests the determination of the masks generation.
   */
  public function testMenuMasksGeneration(): void {
    $connection = Database::getConnection();
    $dumper = new MatcherDumper($connection, $this->state, $this->logger, 'test_routes');

    $collection = new RouteCollection();
    $collection->add('test_route_1', new Route('/test-length-3/{my}/path'));
    $collection->add('test_route_2', new Route('/test-length-3/hello/path'));
    $collection->add('test_route_3', new Route('/test-length-5/{my}/path/marvin/android'));
    $collection->add('test_route_4', new Route('/test-length-7/{my}/path/marvin/android/earth/ursa-minor'));

    $dumper->addRoutes($collection);

    $this->fixtures->createTables($connection);

    $dumper->dump(['provider' => 'test']);
    // Using binary for readability, we expect a 0 at any wildcard slug. They
    // should be ordered from longest to shortest.
    $expected = [
      bindec('1011111'),
      bindec('10111'),
      bindec('111'),
      bindec('101'),
    ];
    $this->assertEquals($expected, $this->state->get('routing.menu_masks.test_routes'));
  }

}
