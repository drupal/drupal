<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Component\Datetime\Time;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\Menu\LocalTaskInterface;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\YamlCacheCollector;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * Tests Drupal\Core\Menu\LocalTaskManager.
 */
#[CoversClass(LocalTaskManager::class)]
#[Group('Menu')]
class LocalTaskManagerTest extends UnitTestCase {

  /**
   * The tested manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  protected $manager;

  /**
   * The mocked argument resolver.
   */
  protected ArgumentResolverInterface $argumentResolver;

  /**
   * The test request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The mocked route provider.
   */
  protected RouteProviderInterface&Stub $routeProvider;

  /**
   * The mocked plugin discovery.
   */
  protected DiscoveryInterface&MockObject $pluginDiscovery;

  /**
   * The plugin factory used in the test.
   */
  protected FactoryInterface&Stub $factory;

  /**
   * The cache backend used in the test.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cacheBackend;

  /**
   * The mocked access manager.
   */
  protected AccessManagerInterface&Stub $accessManager;

  /**
   * The route match.
   */
  protected RouteMatchInterface&Stub $routeMatch;

  /**
   * The mocked account.
   */
  protected AccountInterface&Stub $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->argumentResolver = $this->createStub(ArgumentResolverInterface::class);
    $this->request = new Request();
    $this->routeProvider = $this->createStub(RouteProviderInterface::class);
    $this->pluginDiscovery = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->factory = $this->createStub(FactoryInterface::class);
    $this->cacheBackend = $this->prophesize('Drupal\Core\Cache\CacheBackendInterface');
    $this->accessManager = $this->createStub(AccessManagerInterface::class);
    $this->routeMatch = $this->createStub(RouteMatchInterface::class);
    $this->account = $this->createStub(AccountInterface::class);

    $this->setupLocalTaskManager();
    $this->setupNullCacheabilityMetadataValidation();
  }

  /**
   * Tests the getLocalTasksForRoute method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getLocalTasksForRoute()
   */
  public function testGetLocalTasksForRouteSingleLevelTitle(): void {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $pluginStub = $this->createStub(LocalTaskInterface::class);

    $this->setupFactory($pluginStub);
    $this->setupLocalTaskManager();

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');

    $result = $this->getLocalTasksForRouteResult($pluginStub);

    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the getLocalTasksForRoute method on a child.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getLocalTasksForRoute()
   */
  public function testGetLocalTasksForRouteForChild(): void {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $pluginStub = $this->createStub(LocalTaskInterface::class);

    $this->setupFactory($pluginStub);
    $this->setupLocalTaskManager();

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_child1_page');

    $result = $this->getLocalTasksForRouteResult($pluginStub);

    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the cache of the local task manager with an empty initial cache.
   */
  public function testGetLocalTaskForRouteWithEmptyCache(): void {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $pluginStub = $this->createStub(LocalTaskInterface::class);
    $this->setupFactory($pluginStub);

    $this->setupLocalTaskManager();

    $result = $this->getLocalTasksForRouteResult($pluginStub);

    $this->cacheBackend->get('local_task_plugins:en:menu_local_task_test_tasks_view')
      ->shouldBeCalled();
    $this->cacheBackend->get('local_task_plugins:en')
      ->shouldBeCalled();

    $this->cacheBackend->set('local_task_plugins:en', $definitions, Cache::PERMANENT, ["local_task"])
      ->shouldBeCalled();
    $this->cacheBackend->set('local_task_plugins:en:menu_local_task_test_tasks_view', $this->getLocalTasksCache(), Cache::PERMANENT, ['local_task'])
      ->shouldBeCalled();

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');
    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the cache of the local task manager with a filled initial cache.
   */
  public function testGetLocalTaskForRouteWithFilledCache(): void {
    $this->pluginDiscovery->expects($this->never())
      ->method('getDefinitions');

    $pluginStub = $this->createStub(LocalTaskInterface::class);
    $this->setupFactory($pluginStub);

    $this->setupLocalTaskManager();

    $result = $this->getLocalTasksCache();

    $this->cacheBackend->get('local_task_plugins:en:menu_local_task_test_tasks_view')
      ->willReturn((object) ['data' => $result]);

    $this->cacheBackend->set()
      ->shouldNotBeCalled();

    $result = $this->getLocalTasksForRouteResult($pluginStub);
    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');
    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the getTitle method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getTitle()
   */
  public function testGetTitle(): void {
    // Override the argument resolver so we can set expectations for it.
    $this->argumentResolver = $this->createMock(ArgumentResolverInterface::class);
    $this->setupLocalTaskManager();

    $this->pluginDiscovery->expects($this->never())
      ->method('getDefinitions');

    $menu_local_task = $this->createMock('Drupal\Core\Menu\LocalTaskInterface');
    $menu_local_task->expects($this->once())
      ->method('getTitle');

    $this->argumentResolver->expects($this->once())
      ->method('getArguments')
      ->with($this->request, [$menu_local_task, 'getTitle'])
      ->willReturn([]);

    $this->manager->getTitle($menu_local_task);
  }

  /**
   * Setups the local task manager for the test.
   */
  protected function setupLocalTaskManager(): void {
    $request_stack = new RequestStack();
    $request_stack->push($this->request);
    $module_handler = $this->createStub(ModuleHandlerInterface::class);
    $module_handler
      ->method('getModuleDirectories')
      ->willReturn([]);
    $language_manager = $this->createStub(LanguageManagerInterface::class);
    $language_manager
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));
    $yaml_cache_collector = new YamlCacheCollector('test', new MemoryCache(new Time()), new NullLockBackend(), new Time());

    $this->manager = new LocalTaskManager($this->argumentResolver, $request_stack, $this->routeMatch, $this->routeProvider, $module_handler, $this->cacheBackend->reveal(), $language_manager, $this->accessManager, $this->account, $yaml_cache_collector);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'discovery');
    $property->setValue($this->manager, $this->pluginDiscovery);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'factory');
    $property->setValue($this->manager, $this->factory);

  }

  /**
   * Return some local tasks plugin definitions.
   *
   * @return array
   *   An array of plugin definition keyed by plugin ID.
   */
  protected function getLocalTaskFixtures(): array {
    $definitions = [];
    $definitions['menu_local_task_test_tasks_settings'] = [
      'route_name' => 'menu_local_task_test_tasks_settings',
      'title' => 'Settings',
      'base_route' => 'menu_local_task_test_tasks_view',
    ];
    $definitions['menu_local_task_test_tasks_edit'] = [
      'route_name' => 'menu_local_task_test_tasks_edit',
      'title' => 'Settings',
      'base_route' => 'menu_local_task_test_tasks_view',
      'weight' => 20,
    ];
    // Make this ID different from the route name to catch code that
    // confuses them.
    $definitions['menu_local_task_test_tasks_view.tab'] = [
      'route_name' => 'menu_local_task_test_tasks_view',
      'title' => 'Settings',
      'base_route' => 'menu_local_task_test_tasks_view',
    ];

    $definitions['menu_local_task_test_tasks_view_child1'] = [
      'route_name' => 'menu_local_task_test_tasks_child1_page',
      'title' => 'Settings child #1',
      'parent_id' => 'menu_local_task_test_tasks_view.tab',
    ];
    $definitions['menu_local_task_test_tasks_view_child2'] = [
      'route_name' => 'menu_local_task_test_tasks_child2_page',
      'title' => 'Settings child #2',
      'parent_id' => 'menu_local_task_test_tasks_view.tab',
      'base_route' => 'this_should_be_replaced',
    ];
    // Add the ID and defaults from the LocalTaskManager.
    foreach ($definitions as $id => &$info) {
      $info['id'] = $id;
      $info += [
        'id' => '',
        'route_name' => '',
        'route_parameters' => [],
        'title' => '',
        'base_route' => '',
        'parent_id' => NULL,
        'weight' => 0,
        'options' => [],
        'class' => 'Drupal\Core\Menu\LocalTaskDefault',
      ];
    }
    return $definitions;
  }

  /**
   * Setups the plugin factory with some local task plugins.
   *
   * @param \Drupal\Core\Menu\LocalTaskInterface|\PHPUnit\Framework\MockObject\Stub $pluginStub
   *   The stubbed plugin.
   */
  protected function setupFactory(LocalTaskInterface&Stub $pluginStub): void {
    $map = [];
    foreach ($this->getLocalTaskFixtures() as $info) {
      $map[] = [$info['id'], [], $pluginStub];
    }
    $this->factory
      ->method('createInstance')
      ->willReturnMap($map);
  }

  /**
   * Returns an expected result for getLocalTasksForRoute.
   *
   * @param \Drupal\Core\Menu\LocalTaskInterface|\PHPUnit\Framework\MockObject\Stub $pluginStub
   *   The stubbed plugin.
   *
   * @return array
   *   The expected result, keyed by local task level.
   */
  protected function getLocalTasksForRouteResult(LocalTaskInterface&Stub $pluginStub): array {
    return [
      0 => [
        'menu_local_task_test_tasks_settings' => $pluginStub,
        'menu_local_task_test_tasks_view.tab' => $pluginStub,
        'menu_local_task_test_tasks_edit' => $pluginStub,
      ],
      1 => [
        'menu_local_task_test_tasks_view_child1' => $pluginStub,
        'menu_local_task_test_tasks_view_child2' => $pluginStub,
      ],
    ];
  }

  /**
   * Returns the cache entry expected when running getLocalTaskForRoute().
   *
   * @return array
   *   The expected cache entry.
   */
  protected function getLocalTasksCache(): array {
    $local_task_fixtures = $this->getLocalTaskFixtures();
    $local_tasks = [
      'base_routes' => [
        'menu_local_task_test_tasks_view' => 'menu_local_task_test_tasks_view',
      ],
      'parents' => [
        'menu_local_task_test_tasks_view.tab' => TRUE,
      ],
      'children' => [
        '> menu_local_task_test_tasks_view' => [
          'menu_local_task_test_tasks_settings' => $local_task_fixtures['menu_local_task_test_tasks_settings'],
          'menu_local_task_test_tasks_edit' => $local_task_fixtures['menu_local_task_test_tasks_edit'],
          'menu_local_task_test_tasks_view.tab' => $local_task_fixtures['menu_local_task_test_tasks_view.tab'],
        ],
        'menu_local_task_test_tasks_view.tab' => [
          // The manager will fill in the base_route before caching.
          'menu_local_task_test_tasks_view_child1' => ['base_route' => 'menu_local_task_test_tasks_view'] + $local_task_fixtures['menu_local_task_test_tasks_view_child1'],
          'menu_local_task_test_tasks_view_child2' => ['base_route' => 'menu_local_task_test_tasks_view'] + $local_task_fixtures['menu_local_task_test_tasks_view_child2'],
        ],
      ],
    ];
    $local_tasks['children']['> menu_local_task_test_tasks_view']['menu_local_task_test_tasks_settings']['weight'] = 0;
    $local_tasks['children']['> menu_local_task_test_tasks_view']['menu_local_task_test_tasks_edit']['weight'] = 20 + 1e-6;
    $local_tasks['children']['> menu_local_task_test_tasks_view']['menu_local_task_test_tasks_view.tab']['weight'] = 2e-6;
    $local_tasks['children']['menu_local_task_test_tasks_view.tab']['menu_local_task_test_tasks_view_child1']['weight'] = 3e-6;
    $local_tasks['children']['menu_local_task_test_tasks_view.tab']['menu_local_task_test_tasks_view_child2']['weight'] = 4e-6;
    return $local_tasks;
  }

  /**
   * Tests get tasks build with cacheability metadata.
   */
  public function testGetTasksBuildWithCacheabilityMetadata(): void {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    // Set up some cacheability metadata and ensure its merged together.
    $definitions['menu_local_task_test_tasks_settings']['cache_tags'] = ['tag.example1'];
    $definitions['menu_local_task_test_tasks_settings']['cache_contexts'] = ['context.example1'];
    $definitions['menu_local_task_test_tasks_edit']['cache_tags'] = ['tag.example2'];
    $definitions['menu_local_task_test_tasks_edit']['cache_contexts'] = ['context.example2'];
    // Test the cacheability metadata of access checking.
    $definitions['menu_local_task_test_tasks_view_child1']['access'] = AccessResult::allowed()->addCacheContexts(['user.permissions']);

    $this->setupFactoryAndLocalTaskPlugins($definitions, 'menu_local_task_test_tasks_view');
    $this->setupLocalTaskManager();

    $this->argumentResolver
      ->method('getArguments')
      ->willReturn([]);

    $this->routeMatch
      ->method('getRouteName')
      ->willReturn('menu_local_task_test_tasks_view');
    $this->routeMatch
      ->method('getRawParameters')
      ->willReturn(new InputBag());

    $cacheability = new CacheableMetadata();
    $this->manager->getTasksBuild('menu_local_task_test_tasks_view', $cacheability);

    // Ensure that all cacheability metadata is merged together.
    $this->assertEqualsCanonicalizing(['tag.example1', 'tag.example2'], $cacheability->getCacheTags());
    $this->assertEqualsCanonicalizing(['context.example1', 'context.example2', 'route', 'user.permissions'], $cacheability->getCacheContexts());
  }

  /**
   * Test multiple parallel calls with fibers.
   */
  public function testGetTasksBuildWithFibers(): void {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $active_plugin_id = 'menu_local_task_test_tasks_view';
    $map = [];

    foreach ($definitions as $plugin_id => $info) {
      $mock = $this->prophesize(LocalTaskInterface::class);
      $mock->willImplement(CacheableDependencyInterface::class);
      $mock->getRouteName()->willReturn($info['route_name']);
      $mock->getTitle()->willReturn($info['title']);
      $mock->getRouteParameters(Argument::cetera())->willReturn([]);
      $mock->getOptions(Argument::cetera())->willReturn([]);
      $mock->getActive()->willReturn($plugin_id === $active_plugin_id);
      $mock->getWeight()->willReturn($info['weight'] ?? 0);
      $mock->getCacheContexts()->willReturn([]);
      $mock->getCacheTags()->willReturn([]);
      $mock->getCacheMaxAge()->willReturn(Cache::PERMANENT);
      $map[] = [$info['id'], [], $mock->reveal()];
    }

    // Simulate an access callback that suspends a fiber.
    $this->accessManager
      ->method('checkNamedRoute')
      ->willReturnCallback(function (string $route_name) {
        if ($route_name === 'menu_local_task_test_tasks_edit') {
          \Fiber::suspend();
        }
        return AccessResult::allowed();
      });

    $this->factory
      ->method('createInstance')
      ->willReturnMap($map);
    $this->setupLocalTaskManager();

    $this->argumentResolver
      ->method('getArguments')
      ->willReturn([]);

    $this->routeMatch
      ->method('getRouteName')
      ->willReturn('menu_local_task_test_tasks_view');
    $this->routeMatch
      ->method('getRawParameters')
      ->willReturn(new InputBag());

    $first_fiber = new \Fiber(fn () => $this->manager->getLocalTasks('menu_local_task_test_tasks_view', 0));
    $second_fiber = new \Fiber(fn () => $this->manager->getLocalTasks('menu_local_task_test_tasks_view', 1));

    $fibers = [$first_fiber, $second_fiber];
    $suspended = FALSE;
    do {
      foreach ($fibers as $key => $fiber) {
        if (!$fiber->isStarted()) {
          $fiber->start();
        }
        elseif ($fiber->isSuspended()) {
          $suspended = TRUE;
          $fiber->resume();
        }
        elseif ($fiber->isTerminated()) {
          unset($fibers[$key]);
        }
      }
    } while (!empty($fibers));

    // Ensure that the fibers were suspended at least once to make sure that
    // the expected scenario is tested here.
    $this->assertTrue($suspended);

    // Assert that both fibers return the correct result.
    $this->assertEquals([
      'menu_local_task_test_tasks_settings',
      'menu_local_task_test_tasks_edit',
      'menu_local_task_test_tasks_view.tab',
    ], array_keys($first_fiber->getReturn()['tabs']));
    $this->assertEquals(['menu_local_task_test_tasks_view_child1', 'menu_local_task_test_tasks_view_child2'], array_keys($second_fiber->getReturn()['tabs']));
  }

  protected function setupFactoryAndLocalTaskPlugins(array $definitions, $active_plugin_id): void {
    $map = [];
    $access_manager_map = [];

    foreach ($definitions as $plugin_id => $info) {
      $info += ['access' => AccessResult::allowed()];

      $mock = $this->prophesize(LocalTaskInterface::class);
      $mock->willImplement(CacheableDependencyInterface::class);
      $mock->getRouteName()->willReturn($info['route_name']);
      $mock->getTitle()->willReturn($info['title']);
      $mock->getRouteParameters(Argument::cetera())->willReturn([]);
      $mock->getOptions(Argument::cetera())->willReturn([]);
      $mock->getActive()->willReturn($plugin_id === $active_plugin_id);
      $mock->getWeight()->willReturn($info['weight'] ?? 0);
      $mock->getCacheContexts()->willReturn($info['cache_contexts'] ?? []);
      $mock->getCacheTags()->willReturn($info['cache_tags'] ?? []);
      $mock->getCacheMaxAge()->willReturn($info['cache_max_age'] ?? Cache::PERMANENT);

      $access_manager_map[] = [$info['route_name'], [], $this->account, TRUE, $info['access']];

      $map[] = [$info['id'], [], $mock->reveal()];
    }

    $this->accessManager
      ->method('checkNamedRoute')
      ->willReturnMap($access_manager_map);

    $this->factory
      ->method('createInstance')
      ->willReturnMap($map);
  }

  protected function setupNullCacheabilityMetadataValidation(): void {
    $container = \Drupal::hasContainer() ? \Drupal::getContainer() : new ContainerBuilder();

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);

    foreach ([
      NULL,
      ['user.permissions'],
      ['route'],
      ['route', 'context.example1'],
      ['context.example1', 'route'],
      ['route', 'context.example1', 'context.example2'],
      ['context.example1', 'context.example2', 'route'],
      ['route', 'context.example1', 'context.example2', 'user.permissions'],
    ] as $argument) {
      $cache_context_manager->assertValidTokens($argument)->willReturn(TRUE);
    }

    $container->set('cache_contexts_manager', $cache_context_manager->reveal());
    \Drupal::setContainer($container);
  }

}
