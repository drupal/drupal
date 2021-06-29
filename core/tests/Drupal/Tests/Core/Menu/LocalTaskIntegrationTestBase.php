<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a base unit test for testing existence of local tasks.
 *
 * @todo Add tests for access checking and url building,
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
  protected function setUp() {
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
  protected function getLocalTaskManager($module_dirs, $route_name, $route_params) {
    $manager = $this
      ->getMockBuilder('Drupal\Core\Menu\LocalTaskManager')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $argumentResolver = $this->createMock('Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface');
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'argumentResolver');
    $property->setAccessible(TRUE);
    $property->setValue($manager, $argumentResolver);

    // todo mock a request with a route.
    $request_stack = new RequestStack();
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'requestStack');
    $property->setAccessible(TRUE);
    $property->setValue($manager, $request_stack);

    $accessManager = $this->createMock('Drupal\Core\Access\AccessManagerInterface');
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'accessManager');
    $property->setAccessible(TRUE);
    $property->setValue($manager, $accessManager);

    $route_provider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'routeProvider');
    $property->setAccessible(TRUE);
    $property->setValue($manager, $route_provider);

    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'moduleHandler');
    $property->setAccessible(TRUE);
    $property->setValue($manager, $module_handler);
    // Set all the modules as being existent.
    $module_handler->expects($this->any())
      ->method('moduleExists')
      ->willReturnCallback(function ($module) use ($module_dirs) {
        return isset($module_dirs[$module]);
      });

    $pluginDiscovery = new YamlDiscovery('links.task', $module_dirs);
    $pluginDiscovery = new ContainerDerivativeDiscoveryDecorator($pluginDiscovery);
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($manager, $pluginDiscovery);

    $method = new \ReflectionMethod('Drupal\Core\Menu\LocalTaskManager', 'alterInfo');
    $method->setAccessible(TRUE);
    $method->invoke($manager, 'local_tasks');

    $plugin_stub = $this->createMock('Drupal\Core\Menu\LocalTaskInterface');
    $factory = $this->createMock('Drupal\Component\Plugin\Factory\FactoryInterface');
    $factory->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValue($plugin_stub));
    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($manager, $factory);

    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $manager->setCacheBackend($cache_backend, 'local_task.en', ['local_task']);

    return $manager;
  }

  /**
   * Tests integration for local tasks.
   *
   * @param $route_name
   *   Route name to base task building on.
   * @param $expected_tasks
   *   A list of tasks groups by level expected at the given route
   * @param array $route_params
   *   (optional) A list of route parameters used to resolve tasks.
   */
  protected function assertLocalTasks($route_name, $expected_tasks, $route_params = []) {

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
