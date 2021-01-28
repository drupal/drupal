<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\Routing\RouteProviderTest.
 */

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\MatcherDumper;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\State\State;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\Core\Routing\RoutingFixtures;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Confirm that the default route provider is working correctly.
 *
 * @group Routing
 */
class RouteProviderTest extends KernelTestBase {

  use PathAliasTestTrait;

  /**
   * Modules to enable.
   */
  protected static $modules = [
    'url_alter_test',
    'system',
    'language',
    'path_alias',
  ];

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
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\MemoryBackend
   */
  protected $cache;

  /**
   * The inbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  protected function setUp(): void {
    parent::setUp();
    $this->fixtures = new RoutingFixtures();
    $this->state = new State(new KeyValueMemoryFactory());
    $this->currentPath = new CurrentPathStack(new RequestStack());
    $this->cache = new MemoryBackend();
    $this->pathProcessor = \Drupal::service('path_processor_manager');
    $this->cacheTagsInvalidator = \Drupal::service('cache_tags.invalidator');
    $this->installEntitySchema('path_alias');
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    // Read the incoming path alias for these tests.
    if ($container->hasDefinition('path_alias.path_processor')) {
      $definition = $container->getDefinition('path_alias.path_processor');
      $definition->addTag('path_processor_inbound');
    }
  }

  protected function tearDown(): void {
    $this->fixtures->dropTables(Database::getConnection());

    parent::tearDown();
  }

  /**
   * Confirms that the correct candidate outlines are generated.
   */
  public function testCandidateOutlines() {

    $connection = Database::getConnection();
    $provider = new TestRouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $parts = ['node', '5', 'edit'];

    $candidates = $provider->getCandidateOutlines($parts);

    $candidates = array_flip($candidates);

    $this->assertCount(7, $candidates, 'Correct number of candidates found');
    $this->assertArrayHasKey('/node/5/edit', $candidates);
    $this->assertArrayHasKey('/node/5/%', $candidates);
    $this->assertArrayHasKey('/node/%/edit', $candidates);
    $this->assertArrayHasKey('/node/%/%', $candidates);
    $this->assertArrayHasKey('/node/5', $candidates);
    $this->assertArrayHasKey('/node/%', $candidates);
    $this->assertArrayHasKey('/node', $candidates);
  }

  /**
   * Don't fail when given an empty path.
   */
  public function testEmptyPathCandidatesOutlines() {
    $provider = new TestRouteProvider(Database::getConnection(), $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');
    $candidates = $provider->getCandidateOutlines([]);
    $this->assertCount(0, $candidates, 'Empty parts should return no candidates.');
  }

  /**
   * Confirms that we can find routes with the exact incoming path.
   */
  public function testExactPathMatch() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->sampleRouteCollection());
    $dumper->dump();

    $path = '/path/one';

    $request = Request::create($path, 'GET');

    $routes = $provider->getRouteCollectionForRequest($request);

    foreach ($routes as $route) {
      $this->assertEqual($path, $route->getPath(), 'Found path has correct pattern');
    }
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  public function testOutlinePathMatch() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->complexRouteCollection());
    $dumper->dump();

    $path = '/path/1/one';

    $request = Request::create($path, 'GET');

    $routes = $provider->getRouteCollectionForRequest($request);

    // All of the matching paths have the correct pattern.
    foreach ($routes as $route) {
      $this->assertEqual('/path/%/one', $route->compile()->getPatternOutline(), 'Found path has correct pattern');
    }

    $this->assertCount(2, $routes, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_a'), 'The first matching route was found.');
    $this->assertNotNull($routes->get('route_b'), 'The second matching route was not found.');
  }

  /**
   * Data provider for testMixedCasePaths()
   */
  public function providerMixedCaseRoutePaths() {
    // cSpell:disable
    return [
      ['/path/one', 'route_a'],
      ['/path/two', NULL],
      ['/PATH/one', 'route_a'],
      ['/path/2/one', 'route_b', 'PUT'],
      ['/paTH/3/one', 'route_b', 'PUT'],
      // There should be no lower case of a Hebrew letter.
      ['/somewhere/4/over/the/קainbow', 'route_c'],
      ['/Somewhere/5/over/the/קainboW', 'route_c'],
      ['/another/llama/aboUT/22', 'route_d'],
      ['/another/llama/about/22', 'route_d'],
      ['/place/meΦω', 'route_e', 'HEAD'],
      ['/place/meφΩ', 'route_e', 'HEAD'],
    ];
    // cSpell:enable
  }

  /**
   * Confirms that we find routes using a case-insensitive path match.
   *
   * @dataProvider providerMixedCaseRoutePaths
   */
  public function testMixedCasePaths($path, $expected_route_name, $method = 'GET') {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->mixedCaseRouteCollection());
    $dumper->dump();

    $request = Request::create($path, $method);

    $routes = $provider->getRouteCollectionForRequest($request);

    if ($expected_route_name) {
      $this->assertCount(1, $routes, 'The correct number of routes was found.');
      $this->assertNotNull($routes->get($expected_route_name), 'The first matching route was found.');
    }
    else {
      $this->assertCount(0, $routes, 'No routes matched.');
    }
  }

  /**
   * Data provider for testMixedCasePaths()
   */
  public function providerDuplicateRoutePaths() {
    // When matching routes with the same fit the route with the lowest-sorting
    // name should end up first in the resulting route collection.
    return [
      ['/path/one', 3, 'route_a'],
      ['/PATH/one', 3, 'route_a'],
      ['/path/two', 1, 'route_d'],
      ['/PATH/three', 0],
      ['/place/meΦω', 2, 'route_e'],
      ['/placE/meφΩ', 2, 'route_e'],
    ];
  }

  /**
   * Confirms that we find all routes with the same path.
   *
   * @dataProvider providerDuplicateRoutePaths
   */
  public function testDuplicateRoutePaths($path, $number, $expected_route_name = NULL) {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->duplicatePathsRouteCollection());
    $dumper->dump();

    $request = Request::create($path);
    $routes = $provider->getRouteCollectionForRequest($request);
    $this->assertCount($number, $routes, 'The correct number of routes was found.');
    if ($expected_route_name) {
      $route_name = key(current($routes));
      $this->assertEquals($expected_route_name, $route_name, 'The expected route name was found.');
    }
  }

  /**
   * Confirms RouteProvider::getAllRoutes() extracts information correctly from the database.
   */
  public function testGetAllRoutes() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->SampleRouteCollection());
    $dumper->dump();

    $sample_routes = $this->fixtures->staticSampleRouteCollection();
    $expected_route_count = count($sample_routes);

    $returned_routes = $provider->getAllRoutes();

    $this->assertInstanceOf(\Iterator::class, $returned_routes);
    $this->assertEqual($expected_route_count, count($returned_routes));

    foreach ($returned_routes as $route_name => $route) {
      $this->assertArrayHasKey($route_name, $sample_routes);
      $this->assertEquals($route->getPath(), $sample_routes[$route_name]['path']);
    }
  }

  /**
   * Confirms that a trailing slash on the request does not result in a 404.
   */
  public function testOutlinePathMatchTrailingSlash() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->complexRouteCollection());
    $dumper->dump();

    $path = '/path/1/one/';

    $request = Request::create($path, 'GET');

    $routes = $provider->getRouteCollectionForRequest($request);

    // All of the matching paths have the correct pattern.
    foreach ($routes as $route) {
      $this->assertEqual('/path/%/one', $route->compile()->getPatternOutline(), 'Found path has correct pattern');
    }

    $this->assertCount(2, $routes, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_a'), 'The first matching route was found.');
    $this->assertNotNull($routes->get('route_b'), 'The second matching route was not found.');
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  public function testOutlinePathMatchDefaults() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/path/{value}', [
      'value' => 'poink',
    ]));

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/path';

    $request = Request::create($path, 'GET');

    try {
      $routes = $provider->getRouteCollectionForRequest($request);

      // All of the matching paths have the correct pattern.
      foreach ($routes as $route) {
        $this->assertEqual('/some/path', $route->compile()->getPatternOutline(), 'Found path has correct pattern');
      }

      $this->assertCount(1, $routes, 'The correct number of routes was found.');
      $this->assertNotNull($routes->get('poink'), 'The first matching route was found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matching route found with default argument value.');
    }
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  public function testOutlinePathMatchDefaultsCollision() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/path/{value}', [
      'value' => 'poink',
    ]));
    $collection->add('narf', new Route('/some/path/here'));

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/path';

    $request = Request::create($path, 'GET');

    try {
      $routes = $provider->getRouteCollectionForRequest($request);

      // All of the matching paths have the correct pattern.
      foreach ($routes as $route) {
        $this->assertEqual('/some/path', $route->compile()->getPatternOutline(), 'Found path has correct pattern');
      }

      $this->assertCount(1, $routes, 'The correct number of routes was found.');
      $this->assertNotNull($routes->get('poink'), 'The first matching route was found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matching route found with default argument value.');
    }
  }

  /**
   * Confirms that we can find routes whose pattern would match the request.
   */
  public function testOutlinePathMatchDefaultsCollision2() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/path/{value}', [
      'value' => 'poink',
    ]));
    $collection->add('narf', new Route('/some/path/here'));
    $collection->add('eep', new Route('/something/completely/different'));

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/path/here';

    $request = Request::create($path, 'GET');

    try {
      $routes = $provider->getRouteCollectionForRequest($request);
      $routes_array = $routes->all();

      $this->assertCount(2, $routes, 'The correct number of routes was found.');
      $this->assertEqual(['narf', 'poink'], array_keys($routes_array), 'Ensure the fitness was taken into account.');
      $this->assertNotNull($routes->get('narf'), 'The first matching route was found.');
      $this->assertNotNull($routes->get('poink'), 'The second matching route was found.');
      $this->assertNull($routes->get('eep'), 'Non-matching route was not found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matching route found with default argument value.');
    }
  }

  /**
   * Confirms that we can find multiple routes that match the request equally.
   */
  public function testOutlinePathMatchDefaultsCollision3() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/{value}/path'));
    // Add a second route matching the same path pattern.
    $collection->add('poink2', new Route('/some/{object}/path'));
    $collection->add('narf', new Route('/some/here/path'));
    $collection->add('eep', new Route('/something/completely/different'));

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/over-there/path';

    $request = Request::create($path, 'GET');

    try {
      $routes = $provider->getRouteCollectionForRequest($request);
      $routes_array = $routes->all();

      $this->assertCount(2, $routes, 'The correct number of routes was found.');
      $this->assertEqual(['poink', 'poink2'], array_keys($routes_array), 'Ensure the fitness and name were taken into account in the sort.');
      $this->assertNotNull($routes->get('poink'), 'The first matching route was found.');
      $this->assertNotNull($routes->get('poink2'), 'The second matching route was found.');
      $this->assertNull($routes->get('eep'), 'Non-matching route was not found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matching route found with default argument value.');
    }
  }

  /**
   * Tests a route with a 0 as value.
   */
  public function testOutlinePathMatchZero() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $collection = new RouteCollection();
    $collection->add('poink', new Route('/some/path/{value}'));

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($collection);
    $dumper->dump();

    $path = '/some/path/0';

    $request = Request::create($path, 'GET');

    try {
      $routes = $provider->getRouteCollectionForRequest($request);

      // All of the matching paths have the correct pattern.
      foreach ($routes as $route) {
        $this->assertEqual('/some/path/%', $route->compile()->getPatternOutline(), 'Found path has correct pattern');
      }

      $this->assertCount(1, $routes, 'The correct number of routes was found.');
    }
    catch (ResourceNotFoundException $e) {
      $this->fail('No matchout route found with 0 as argument value');
    }
  }

  /**
   * Confirms that an exception is thrown when no matching path is found.
   */
  public function testOutlinePathNoMatch() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->complexRouteCollection());
    $dumper->dump();

    $path = '/no/such/path';

    $request = Request::create($path, 'GET');

    $routes = $provider->getRoutesByPattern($path);
    $this->assertEmpty($routes, 'No path found with this pattern.');

    $collection = $provider->getRouteCollectionForRequest($request);
    $this->assertEmpty($collection, 'Empty route collection found with this pattern.');
  }

  /**
   * Tests that route caching works.
   */
  public function testRouteCaching() {
    $connection = Database::getConnection();
    $language_manager = \Drupal::languageManager();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes', $language_manager);

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->sampleRouteCollection());
    $dumper->addRoutes($this->fixtures->complexRouteCollection());
    $dumper->dump();

    // A simple path.
    $path = '/path/add/one';
    $request = Request::create($path, 'GET');
    $provider->getRouteCollectionForRequest($request);

    $cache = $this->cache->get('route:[language]=en:/path/add/one:');
    $this->assertEqual('/path/add/one', $cache->data['path']);
    $this->assertEqual([], $cache->data['query']);
    $this->assertCount(3, $cache->data['routes']);

    // A path with query parameters.
    $path = '/path/add/one?foo=bar';
    $request = Request::create($path, 'GET');
    $provider->getRouteCollectionForRequest($request);

    $cache = $this->cache->get('route:[language]=en:/path/add/one:foo=bar');
    $this->assertEqual('/path/add/one', $cache->data['path']);
    $this->assertEqual(['foo' => 'bar'], $cache->data['query']);
    $this->assertCount(3, $cache->data['routes']);

    // A path with placeholders.
    $path = '/path/1/one';
    $request = Request::create($path, 'GET');
    $provider->getRouteCollectionForRequest($request);

    $cache = $this->cache->get('route:[language]=en:/path/1/one:');
    $this->assertEqual('/path/1/one', $cache->data['path']);
    $this->assertEqual([], $cache->data['query']);
    $this->assertCount(2, $cache->data['routes']);

    // A path with a path alias.
    $this->createPathAlias('/path/add/one', '/path/add-one');
    /** @var \Drupal\path_alias\AliasManagerInterface $alias_manager */
    $alias_manager = \Drupal::service('path_alias.manager');
    $alias_manager->cacheClear();

    $path = '/path/add-one';
    $request = Request::create($path, 'GET');
    $provider->getRouteCollectionForRequest($request);

    $cache = $this->cache->get('route:[language]=en:/path/add-one:');
    $this->assertEqual('/path/add/one', $cache->data['path']);
    $this->assertEqual([], $cache->data['query']);
    $this->assertCount(3, $cache->data['routes']);

    // Test with a different current language by switching out the default
    // language.
    $swiss = ConfigurableLanguage::createFromLangcode('gsw-berne');
    $language_manager->reset();
    \Drupal::service('language.default')->set($swiss);

    $path = '/path/add-one';
    $request = Request::create($path, 'GET');
    $provider->getRouteCollectionForRequest($request);

    $cache = $this->cache->get('route:[language]=gsw-berne:/path/add-one:');
    $this->assertEquals('/path/add/one', $cache->data['path']);
    $this->assertEquals([], $cache->data['query']);
    $this->assertCount(3, $cache->data['routes']);
  }

  /**
   * Test RouteProvider::getRouteByName() and RouteProvider::getRoutesByNames().
   */
  public function testRouteByName() {
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);

    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->sampleRouteCollection());
    $dumper->dump();

    $route = $provider->getRouteByName('route_a');
    $this->assertEqual('/path/one', $route->getPath(), 'The right route pattern was found.');
    $this->assertEqual(['GET'], $route->getMethods(), 'The right route method was found.');
    $route = $provider->getRouteByName('route_b');
    $this->assertEqual('/path/one', $route->getPath(), 'The right route pattern was found.');
    $this->assertEqual(['PUT'], $route->getMethods(), 'The right route method was found.');

    $exception_thrown = FALSE;
    try {
      $provider->getRouteByName('invalid_name');
    }
    catch (RouteNotFoundException $e) {
      $exception_thrown = TRUE;
    }
    $this->assertTrue($exception_thrown, 'Random route was not found.');

    $routes = $provider->getRoutesByNames(['route_c', 'route_d', $this->randomMachineName()]);
    $this->assertCount(2, $routes, 'Only two valid routes found.');
    $this->assertEqual('/path/two', $routes['route_c']->getPath());
    $this->assertEqual('/path/three', $routes['route_d']->getPath());
  }

  /**
   * Ensures that the routing system is capable of extreme long patterns.
   */
  public function testGetRoutesByPatternWithLongPatterns() {
    $connection = Database::getConnection();
    $provider = new TestRouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);
    // This pattern has only 3 parts, so we will get candidates, but no routes,
    // even though we have not dumped the routes yet.
    $shortest = '/test/1/test2';
    $result = $provider->getRoutesByPattern($shortest);
    $this->assertEqual(0, $result->count());
    $candidates = $provider->getCandidateOutlines(explode('/', trim($shortest, '/')));
    $this->assertCount(7, $candidates);
    // A longer patten is not found and returns no candidates
    $path_to_test = '/test/1/test2/2/test3/3/4/5/6/test4';
    $result = $provider->getRoutesByPattern($path_to_test);
    $this->assertEqual(0, $result->count());
    $candidates = $provider->getCandidateOutlines(explode('/', trim($path_to_test, '/')));
    $this->assertCount(0, $candidates);

    // Add a matching route and dump it.
    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $collection = new RouteCollection();
    $collection->add('long_pattern', new Route('/test/{v1}/test2/{v2}/test3/{v3}/{v4}/{v5}/{v6}/test4'));
    $dumper->addRoutes($collection);
    $dumper->dump();

    $result = $provider->getRoutesByPattern($path_to_test);
    $this->assertEqual(1, $result->count());
    // We can't compare the values of the routes directly, nor use
    // spl_object_hash() because they are separate instances.
    $this->assertEqual(serialize($collection->get('long_pattern')), serialize($result->get('long_pattern')), 'The right route was found.');
    // We now have a single candidate outline.
    $candidates = $provider->getCandidateOutlines(explode('/', trim($path_to_test, '/')));
    $this->assertCount(1, $candidates);
    // Longer and shorter patterns are not found. Both are longer than 3, so
    // we should not have any candidates either. The fact that we do not
    // get any candidates for a longer path is a security feature.
    $longer = '/test/1/test2/2/test3/3/4/5/6/test4/trailing/more/parts';
    $result = $provider->getRoutesByPattern($longer);
    $this->assertEqual(0, $result->count());
    $candidates = $provider->getCandidateOutlines(explode('/', trim($longer, '/')));
    $this->assertCount(1, $candidates);
    $shorter = '/test/1/test2/2/test3';
    $result = $provider->getRoutesByPattern($shorter);
    $this->assertEqual(0, $result->count());
    $candidates = $provider->getCandidateOutlines(explode('/', trim($shorter, '/')));
    $this->assertCount(0, $candidates);
    // This pattern has only 3 parts, so we will get candidates, but no routes.
    // This result is unchanged by running the dumper.
    $result = $provider->getRoutesByPattern($shortest);
    $this->assertEqual(0, $result->count());
    $candidates = $provider->getCandidateOutlines(explode('/', trim($shortest, '/')));
    $this->assertCount(7, $candidates);
  }

  /**
   * Tests getRoutesPaged().
   *
   * @group legacy
   */
  public function testGetRoutesPaged() {
    $this->expectDeprecation('Drupal\Core\Routing\RouteProvider::getRoutesPaged() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3151009');
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);
    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->sampleRouteCollection());
    $dumper->dump();

    $fixture_routes = $this->fixtures->staticSampleRouteCollection();

    // Query all the routes.
    $routes = $provider->getRoutesPaged(0);
    $this->assertEqual(array_keys($fixture_routes), array_keys($routes));

    // Query non routes.
    $routes = $provider->getRoutesPaged(0, 0);
    $this->assertEqual([], array_keys($routes));

    // Query a limited sets of routes.
    $routes = $provider->getRoutesPaged(1, 2);
    $this->assertEqual(array_slice(array_keys($fixture_routes), 1, 2), array_keys($routes));
  }

  /**
   * Tests getRoutesCount().
   *
   * @group legacy
   */
  public function testGetRoutesCount() {
    $this->expectDeprecation('Drupal\Core\Routing\RouteProvider::getRoutesCount() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3151009');
    $connection = Database::getConnection();
    $provider = new RouteProvider($connection, $this->state, $this->currentPath, $this->cache, $this->pathProcessor, $this->cacheTagsInvalidator, 'test_routes');

    $this->fixtures->createTables($connection);
    $dumper = new MatcherDumper($connection, $this->state, 'test_routes');
    $dumper->addRoutes($this->fixtures->sampleRouteCollection());
    $dumper->dump();
    $this->assertEqual(5, $provider->getRoutesCount());
  }

}

class TestRouteProvider extends RouteProvider {

  public function getCandidateOutlines(array $parts) {
    return parent::getCandidateOutlines($parts);
  }

}
