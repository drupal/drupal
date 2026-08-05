<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlCacheCollectorDiscovery;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\YamlCacheCollector;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * Defines a base unit test for testing existence of local tasks.
 *
 * @todo Add tests for access checking and URL building,
 *   https://www.drupal.org/node/2112245.
 */
abstract class LocalTaskIntegrationTestBase extends UnitTestCase {

  /**
   * A list of module directories used for YAML searching.
   *
   * @var array
   */
  protected $directoryList;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $config_factory = $this->getConfigFactoryStub([]);
    $container->set('config.factory', $config_factory);
    $container->setParameter('app.root', $this->root);
    \Drupal::setContainer($container);
    $this->container = $container;
  }

  /**
   * Sets up the local task manager for the test.
   */
  protected function getLocalTaskManager(array $module_dirs, string $route_name, array $route_params): LocalTaskManager {
    $manager = $this
      ->getMockBuilder('Drupal\Core\Menu\LocalTaskManager')
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();

    $argumentResolver = $this->createMock('Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface');
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'argumentResolver');
    $property->setValue($manager, $argumentResolver);

    // @todo Mock a request with a route.
    $request_stack = new RequestStack();
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'requestStack');
    $property->setValue($manager, $request_stack);

    $accessManager = $this->createMock('Drupal\Core\Access\AccessManagerInterface');
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'accessManager');
    $property->setValue($manager, $accessManager);

    $route_provider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'routeProvider');
    $property->setValue($manager, $route_provider);

    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'moduleHandler');
    $property->setValue($manager, $module_handler);
    // Set all the modules as being existent.
    $module_handler->expects($this->any())
      ->method('moduleExists')
      ->willReturnCallback(function ($module) use ($module_dirs) {
        return isset($module_dirs[$module]);
      });

    $language = $this->createStub(LanguageInterface::class);
    $language->method('getId')
      ->willReturn('en');
    $language_manager = $this->createStub(LanguageManagerInterface::class);
    $language_manager->method('getCurrentLanguage')
      ->willReturn($language);

    $yaml_cache_collector = new YamlCacheCollector('test', new MemoryCache(new Time()), new NullLockBackend(), new Time());
    $manager = new LocalTaskManager(
      $this->createStub(ArgumentResolverInterface::class),
      new RequestStack(),
      $this->createStub(RouteMatchInterface::class),
      $this->createStub(RouteProviderInterface::class),
      $module_handler,
      $this->createStub(CacheBackendInterface::class),
      $language_manager,
      $this->createStub(AccessManagerInterface::class),
      $this->createStub(AccountInterface::class),
      $yaml_cache_collector,
    );

    $pluginDiscovery = new YamlCacheCollectorDiscovery('links.task', $module_dirs, $yaml_cache_collector);
    $pluginDiscovery = new ContainerDerivativeDiscoveryDecorator($pluginDiscovery);
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'discovery');
    $property->setValue($manager, $pluginDiscovery);

    $method = new \ReflectionMethod('Drupal\Core\Menu\LocalTaskManager', 'alterInfo');
    $method->invoke($manager, 'local_tasks');

    $plugin_stub = $this->createMock('Drupal\Core\Menu\LocalTaskInterface');
    $factory = $this->createMock('Drupal\Component\Plugin\Factory\FactoryInterface');
    $factory->expects($this->any())
      ->method('createInstance')
      ->willReturn($plugin_stub);
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'factory');
    $property->setValue($manager, $factory);

    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $manager->setCacheBackend($cache_backend, 'local_task.en', ['local_task']);

    return $manager;
  }

  /**
   * Tests integration for local tasks.
   *
   * @param string $route_name
   *   Route name to base task building on.
   * @param array $expected_tasks
   *   A list of tasks groups by level expected at the given route.
   * @param array $route_params
   *   (optional) A list of route parameters used to resolve tasks.
   */
  protected function assertLocalTasks(string $route_name, array $expected_tasks, array $route_params = []): void {

    $directory_list = [];
    foreach ($this->directoryList as $key => $value) {
      $directory_list[$key] = $this->root . '/' . $value;
    }

    $manager = $this->getLocalTaskManager($directory_list, $route_name, $route_params);

    $tmp_tasks = $manager->getLocalTasksForRoute($route_name);

    // At this point we're just testing existence so pull out keys and then
    // compare.
    //
    // Deeper testing would require a functioning factory which because we are
    // using the DefaultPluginManager base means we get into dependency soup
    // because its factories create method and pulling services off the \Drupal
    // container.
    $tasks = [];
    foreach ($tmp_tasks as $level => $level_tasks) {
      $tasks[$level] = array_keys($level_tasks);
    }
    $this->assertEquals($expected_tasks, $tasks);
  }

}
