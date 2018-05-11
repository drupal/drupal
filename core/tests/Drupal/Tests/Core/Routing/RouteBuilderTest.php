<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\RouteBuilderTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Routing\RouteBuilder;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\RouteBuilder
 * @group Routing
 */
class RouteBuilderTest extends UnitTestCase {

  /**
   * The actual tested route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilder
   */
  protected $routeBuilder;

  /**
   * The mocked matcher dumper.
   *
   * @var \Drupal\Core\Routing\MatcherDumperInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dumper;

  /**
   * The mocked lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $lock;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dispatcher;

  /**
   * The mocked YAML discovery.
   *
   * @var \Drupal\Core\Discovery\YamlDiscovery|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $yamlDiscovery;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * @var \Drupal\Core\Access\CheckProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $checkProvider;

  protected function setUp() {
    $this->dumper = $this->getMock('Drupal\Core\Routing\MatcherDumperInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->dispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->controllerResolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->yamlDiscovery = $this->getMockBuilder('\Drupal\Core\Discovery\YamlDiscovery')
      ->disableOriginalConstructor()
      ->getMock();
    $this->checkProvider = $this->getMock('\Drupal\Core\Access\CheckProviderInterface');

    $this->routeBuilder = new TestRouteBuilder($this->dumper, $this->lock, $this->dispatcher, $this->moduleHandler, $this->controllerResolver, $this->checkProvider);
    $this->routeBuilder->setYamlDiscovery($this->yamlDiscovery);
  }

  /**
   * Tests that the route rebuilding both locks and unlocks.
   */
  public function testRebuildLockingUnlocking() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->will($this->returnValue(TRUE));

    $this->lock->expects($this->once())
      ->method('release')
      ->with('router_rebuild');

    $this->yamlDiscovery->expects($this->any())
      ->method('findAll')
      ->will($this->returnValue([]));

    $this->assertTrue($this->routeBuilder->rebuild());
  }

  /**
   * Tests route rebuilding with a blocking lock.
   */
  public function testRebuildBlockingLock() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->will($this->returnValue(FALSE));

    $this->lock->expects($this->once())
      ->method('wait')
      ->with('router_rebuild');

    $this->lock->expects($this->never())
      ->method('release');

    $this->yamlDiscovery->expects($this->never())
      ->method('findAll');

    $this->assertFalse($this->routeBuilder->rebuild());
  }

  /**
   * Tests that provided routes by a module is put into the dumper/dispatcher.
   *
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   */
  public function testRebuildWithStaticModuleRoutes() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->will($this->returnValue(TRUE));

    $routing_fixtures = new RoutingFixtures();
    $routes = $routing_fixtures->staticSampleRouteCollection();

    $this->yamlDiscovery->expects($this->once())
      ->method('findAll')
      ->will($this->returnValue(['test_module' => $routes]));

    $route_collection = $routing_fixtures->sampleRouteCollection();
    $route_build_event = new RouteBuildEvent($route_collection);

    // Ensure that the alter routes events are fired.
    $this->dispatcher->expects($this->at(0))
      ->method('dispatch')
      ->with(RoutingEvents::DYNAMIC, $route_build_event);

    $this->dispatcher->expects($this->at(1))
      ->method('dispatch')
      ->with(RoutingEvents::ALTER, $route_build_event);

    // Ensure that access checks are set.
    $this->checkProvider->expects($this->once())
      ->method('setChecks')
      ->with($route_collection);

    // Ensure that the routes are set to the dumper and dumped.
    $this->dumper->expects($this->at(0))
      ->method('addRoutes')
      ->with($route_collection);
    $this->dumper->expects($this->at(1))
      ->method('dump')
      ->with();

    $this->assertTrue($this->routeBuilder->rebuild());
  }

  /**
   * Tests the rebuild with routes provided by a callback.
   *
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   */
  public function testRebuildWithProviderBasedRoutes() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->will($this->returnValue(TRUE));

    $this->yamlDiscovery->expects($this->once())
      ->method('findAll')
      ->will($this->returnValue([
        'test_module' => [
          'route_callbacks' => [
            '\Drupal\Tests\Core\Routing\TestRouteSubscriber::routesFromArray',
            'test_module.route_service:routesFromCollection',
          ],
        ],
      ]));

    $container = new ContainerBuilder();
    $container->set('test_module.route_service', new TestRouteSubscriber());
    $this->controllerResolver->expects($this->any())
      ->method('getControllerFromDefinition')
      ->will($this->returnCallback(function ($controller) use ($container) {
        $count = substr_count($controller, ':');
        if ($count == 1) {
          list($service, $method) = explode(':', $controller, 2);
          $object = $container->get($service);
        }
        else {
          list($class, $method) = explode('::', $controller, 2);
          $object = new $class();
        }
        return [$object, $method];
      }));

    $route_collection_filled = new RouteCollection();
    $route_collection_filled->add('test_route.1', new Route('/test-route/1'));
    $route_collection_filled->add('test_route.2', new Route('/test-route/2'));

    $route_build_event = new RouteBuildEvent($route_collection_filled);

    // Ensure that the alter routes events are fired.
    $this->dispatcher->expects($this->at(0))
      ->method('dispatch')
      ->with(RoutingEvents::DYNAMIC, $route_build_event);

    $this->dispatcher->expects($this->at(1))
      ->method('dispatch')
      ->with(RoutingEvents::ALTER, $route_build_event);

    // Ensure that access checks are set.
    $this->checkProvider->expects($this->once())
      ->method('setChecks')
      ->with($route_collection_filled);

    // Ensure that the routes are set to the dumper and dumped.
    $this->dumper->expects($this->at(0))
      ->method('addRoutes')
      ->with($route_collection_filled);
    $this->dumper->expects($this->at(1))
      ->method('dump');

    $this->assertTrue($this->routeBuilder->rebuild());
  }

  /**
   * Tests \Drupal\Core\Routing\RouteBuilder::rebuildIfNeeded() method.
   */
  public function testRebuildIfNeeded() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->will($this->returnValue(TRUE));

    $this->lock->expects($this->once())
      ->method('release')
      ->with('router_rebuild');

    $this->yamlDiscovery->expects($this->any())
      ->method('findAll')
      ->will($this->returnValue([]));

    $this->routeBuilder->setRebuildNeeded();

    // This will trigger a successful rebuild.
    $this->assertTrue($this->routeBuilder->rebuildIfNeeded());

    // This will not trigger a rebuild.
    $this->assertFalse($this->routeBuilder->rebuildIfNeeded());
  }

}

/**
 * Extends the core route builder with a setter method for the YAML discovery.
 */
class TestRouteBuilder extends RouteBuilder {

  /**
   * The mocked YAML discovery.
   *
   * @var \Drupal\Core\Discovery\YamlDiscovery|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $yamlDiscovery;

  /**
   * Sets the YAML discovery.
   *
   * @param \Drupal\Core\Discovery\YamlDiscovery $yaml_discovery
   *   The YAML discovery to set.
   */
  public function setYamlDiscovery(YamlDiscovery $yaml_discovery) {
    $this->yamlDiscovery = $yaml_discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteDefinitions() {
    return $this->yamlDiscovery->findAll();
  }

}

/**
 * Provides a callback for route definition.
 */
class TestRouteSubscriber {

  public function routesFromArray() {
    return [
      'test_route.1' => new Route('/test-route/1'),
    ];
  }

  public function routesFromCollection() {
    $collection = new RouteCollection();
    $collection->add('test_route.2', new Route('/test-route/2'));
    return $collection;
  }

}
