<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Menu\LocalTaskInterface;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Menu\LocalTaskManager
 * @group Menu
 */
class LocalTaskManagerTest extends UnitTestCase {

  /**
   * The tested manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  protected $manager;

  /**
   * The mocked argument resolver.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $argumentResolver;

  /**
   * The test request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The mocked route provider.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeProvider;

  /**
   * The mocked plugin discovery.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginDiscovery;

  /**
   * The plugin factory used in the test.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $factory;

  /**
   * The cache backend used in the test.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $accessManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->argumentResolver = $this->createMock('Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface');
    $this->request = new Request();
    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->pluginDiscovery = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->factory = $this->createMock('Drupal\Component\Plugin\Factory\FactoryInterface');
    $this->cacheBackend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->accessManager = $this->createMock('Drupal\Core\Access\AccessManagerInterface');
    $this->routeMatch = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');

    $this->setupLocalTaskManager();
    $this->setupNullCacheabilityMetadataValidation();
  }

  /**
   * Tests the getLocalTasksForRoute method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getLocalTasksForRoute()
   */
  public function testGetLocalTasksForRouteSingleLevelTitle() {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $mock_plugin = $this->createMock('Drupal\Core\Menu\LocalTaskInterface');

    $this->setupFactory($mock_plugin);
    $this->setupLocalTaskManager();

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');

    $result = $this->getLocalTasksForRouteResult($mock_plugin);

    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the getLocalTasksForRoute method on a child.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getLocalTasksForRoute()
   */
  public function testGetLocalTasksForRouteForChild() {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $mock_plugin = $this->createMock('Drupal\Core\Menu\LocalTaskInterface');

    $this->setupFactory($mock_plugin);
    $this->setupLocalTaskManager();

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_child1_page');

    $result = $this->getLocalTasksForRouteResult($mock_plugin);

    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the cache of the local task manager with an empty initial cache.
   */
  public function testGetLocalTaskForRouteWithEmptyCache() {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $mock_plugin = $this->createMock('Drupal\Core\Menu\LocalTaskInterface');
    $this->setupFactory($mock_plugin);

    $this->setupLocalTaskManager();

    $result = $this->getLocalTasksForRouteResult($mock_plugin);

    $this->cacheBackend->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(
        ['local_task_plugins:en:menu_local_task_test_tasks_view'],
        ['local_task_plugins:en'],
      );
    $this->cacheBackend->expects($this->exactly(2))
      ->method('set')
      ->willReturnOnConsecutiveCalls(
        ['local_task_plugins:en', $definitions, Cache::PERMANENT],
        ['local_task_plugins:en:menu_local_task_test_tasks_view', $this->getLocalTasksCache(), Cache::PERMANENT, ['local_task']],
      );

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');
    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the cache of the local task manager with a filled initial cache.
   */
  public function testGetLocalTaskForRouteWithFilledCache() {
    $this->pluginDiscovery->expects($this->never())
      ->method('getDefinitions');

    $mock_plugin = $this->createMock('Drupal\Core\Menu\LocalTaskInterface');
    $this->setupFactory($mock_plugin);

    $this->setupLocalTaskManager();

    $result = $this->getLocalTasksCache($mock_plugin);

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('local_task_plugins:en:menu_local_task_test_tasks_view')
      ->willReturn((object) ['data' => $result]);

    $this->cacheBackend->expects($this->never())
      ->method('set');

    $result = $this->getLocalTasksForRouteResult($mock_plugin);
    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');
    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the getTitle method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getTitle()
   */
  public function testGetTitle() {
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
  protected function setupLocalTaskManager() {
    $request_stack = new RequestStack();
    $request_stack->push($this->request);
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->any())
      ->method('getModuleDirectories')
      ->willReturn([]);
    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $this->manager = new LocalTaskManager($this->argumentResolver, $request_stack, $this->routeMatch, $this->routeProvider, $module_handler, $this->cacheBackend, $language_manager, $this->accessManager, $this->account);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->pluginDiscovery);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->factory);

  }

  /**
   * Return some local tasks plugin definitions.
   *
   * @return array
   *   An array of plugin definition keyed by plugin ID.
   */
  protected function getLocalTaskFixtures() {
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
   * @param \PHPUnit\Framework\MockObject\MockObject $mock_plugin
   *   The mock plugin.
   */
  protected function setupFactory($mock_plugin) {
    $map = [];
    foreach ($this->getLocalTaskFixtures() as $info) {
      $map[] = [$info['id'], [], $mock_plugin];
    }
    $this->factory->expects($this->any())
      ->method('createInstance')
      ->willReturnMap($map);
  }

  /**
   * Returns an expected result for getLocalTasksForRoute.
   *
   * @param \PHPUnit\Framework\MockObject\MockObject $mock_plugin
   *   The mock plugin.
   *
   * @return array
   *   The expected result, keyed by local task level.
   */
  protected function getLocalTasksForRouteResult($mock_plugin) {
    $result = [
      0 => [
        'menu_local_task_test_tasks_settings' => $mock_plugin,
        'menu_local_task_test_tasks_view.tab' => $mock_plugin,
        'menu_local_task_test_tasks_edit' => $mock_plugin,
      ],
      1 => [
        'menu_local_task_test_tasks_view_child1' => $mock_plugin,
        'menu_local_task_test_tasks_view_child2' => $mock_plugin,
      ],
    ];
    return $result;
  }

  /**
   * Returns the cache entry expected when running getLocalTaskForRoute().
   *
   * @return array
   */
  protected function getLocalTasksCache() {
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
   * @covers ::getTasksBuild
   */
  public function testGetTasksBuildWithCacheabilityMetadata() {
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

    $this->argumentResolver->expects($this->any())
      ->method('getArguments')
      ->willReturn([]);

    $this->routeMatch->expects($this->any())
      ->method('getRouteName')
      ->willReturn('menu_local_task_test_tasks_view');
    $this->routeMatch->expects($this->any())
      ->method('getRawParameters')
      ->willReturn(new ParameterBag());

    $cacheability = new CacheableMetadata();
    $this->manager->getTasksBuild('menu_local_task_test_tasks_view', $cacheability);

    // Ensure that all cacheability metadata is merged together.
    $this->assertEqualsCanonicalizing(['tag.example1', 'tag.example2'], $cacheability->getCacheTags());
    $this->assertEqualsCanonicalizing(['context.example1', 'context.example2', 'route', 'user.permissions'], $cacheability->getCacheContexts());
  }

  protected function setupFactoryAndLocalTaskPlugins(array $definitions, $active_plugin_id) {
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

    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->willReturnMap($access_manager_map);

    $this->factory->expects($this->any())
      ->method('createInstance')
      ->willReturnMap($map);
  }

  protected function setupNullCacheabilityMetadataValidation() {
    $container = \Drupal::hasContainer() ? \Drupal::getContainer() : new ContainerBuilder();

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);

    foreach ([NULL, ['user.permissions'], ['route'], ['route', 'context.example1'], ['context.example1', 'route'], ['route', 'context.example1', 'context.example2'], ['context.example1', 'context.example2', 'route'], ['route', 'context.example1', 'context.example2', 'user.permissions']] as $argument) {
      $cache_context_manager->assertValidTokens($argument)->willReturn(TRUE);
    }

    $container->set('cache_contexts_manager', $cache_context_manager->reveal());
    \Drupal::setContainer($container);
  }

}
