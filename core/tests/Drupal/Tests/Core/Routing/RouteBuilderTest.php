<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Routing\RouteBuilder;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
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
   * @var \Drupal\Core\Routing\MatcherDumperInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $dumper;

  /**
   * The mocked lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $dispatcher;

  /**
   * The mocked YAML discovery.
   *
   * @var \Drupal\Core\Discovery\YamlDiscovery|\PHPUnit\Framework\MockObject\MockObject
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
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $controllerResolver;

  /**
   * @var \Drupal\Core\Access\CheckProviderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $checkProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dumper = $this->createMock('Drupal\Core\Routing\MatcherDumperInterface');
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->dispatcher = $this->prophesize('\Symfony\Contracts\EventDispatcher\EventDispatcherInterface');
    $this->dispatcher->dispatch(Argument::cetera(), Argument::cetera())->willReturnArgument(0);
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->controllerResolver = $this->createMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->yamlDiscovery = $this->getMockBuilder('\Drupal\Core\Discovery\YamlDiscovery')
      ->disableOriginalConstructor()
      ->getMock();
    $this->checkProvider = $this->createMock('\Drupal\Core\Access\CheckProviderInterface');

    $this->routeBuilder = new TestRouteBuilder($this->dumper, $this->lock, $this->dispatcher->reveal(), $this->moduleHandler, $this->controllerResolver, $this->checkProvider);
    $this->routeBuilder->setYamlDiscovery($this->yamlDiscovery);
  }

  /**
   * Tests that the route rebuilding both locks and unlocks.
   */
  public function testRebuildLockingUnlocking(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->willReturn(TRUE);

    $this->lock->expects($this->once())
      ->method('release')
      ->with('router_rebuild');

    $this->yamlDiscovery->expects($this->any())
      ->method('findAll')
      ->willReturn([]);

    $this->assertTrue($this->routeBuilder->rebuild());
  }

  /**
   * Tests route rebuilding with a blocking lock.
   */
  public function testRebuildBlockingLock(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->willReturn(FALSE);

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
  public function testRebuildWithStaticModuleRoutes(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->willReturn(TRUE);

    $routing_fixtures = new RoutingFixtures();
    $routes = $routing_fixtures->staticSampleRouteCollection();

    $this->yamlDiscovery->expects($this->once())
      ->method('findAll')
      ->willReturn(['test_module' => $routes]);

    $route_collection = $routing_fixtures->sampleRouteCollection();
    foreach ($route_collection->all() as $route) {
      $route->setOption('compiler_class', RouteCompiler::class);
    }
    $route_build_event = new RouteBuildEvent($route_collection);

    // Ensure that the alter routes events are fired.
    $this->dispatcher->dispatch($route_build_event, RoutingEvents::DYNAMIC)
      ->shouldBeCalled();
    $this->dispatcher->dispatch($route_build_event, RoutingEvents::ALTER)
      ->shouldBeCalled();

    // Ensure that access checks are set.
    $this->checkProvider->expects($this->once())
      ->method('setChecks')
      ->with($route_collection);

    // Ensure that the routes are set to the dumper and dumped.
    $this->dumper->expects($this->once())
      ->method('addRoutes')
      ->with($route_collection);
    $this->dumper->expects($this->once())
      ->method('dump')
      ->with();

    $this->assertTrue($this->routeBuilder->rebuild());
  }

  /**
   * Tests the rebuild with routes provided by a callback.
   *
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   */
  public function testRebuildWithProviderBasedRoutes(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->willReturn(TRUE);

    $this->yamlDiscovery->expects($this->once())
      ->method('findAll')
      ->willReturn([
        'test_module' => [
          'route_callbacks' => [
            '\Drupal\Tests\Core\Routing\TestRouteSubscriber::routesFromArray',
            'test_module.route_service:routesFromCollection',
          ],
        ],
      ]);

    $container = new ContainerBuilder();
    $container->set('test_module.route_service', new TestRouteSubscriber());
    $this->controllerResolver->expects($this->any())
      ->method('getControllerFromDefinition')
      ->willReturnCallback(function ($controller) use ($container) {
        $count = substr_count($controller, ':');
        if ($count == 1) {
          [$service, $method] = explode(':', $controller, 2);
          $object = $container->get($service);
        }
        else {
          [$class, $method] = explode('::', $controller, 2);
          $object = new $class();
        }

        return [$object, $method];
      });

    $route_collection_filled = new RouteCollection();
    $route_collection_filled->add('test_route.1', new Route('/test-route/1'));
    $route_collection_filled->add('test_route.2', new Route('/test-route/2'));

    $route_build_event = new RouteBuildEvent($route_collection_filled);

    // Ensure that the alter routes events are fired.
    $this->dispatcher->dispatch($route_build_event, RoutingEvents::DYNAMIC)
      ->shouldBeCalled();
    $this->dispatcher->dispatch($route_build_event, RoutingEvents::ALTER)
      ->shouldBeCalled();

    // Ensure that access checks are set.
    $this->checkProvider->expects($this->once())
      ->method('setChecks')
      ->with($route_collection_filled);

    // Ensure that the routes are set to the dumper and dumped.
    $this->dumper->expects($this->once())
      ->method('addRoutes')
      ->with($route_collection_filled);
    $this->dumper->expects($this->once())
      ->method('dump');

    $this->assertTrue($this->routeBuilder->rebuild());
  }

  /**
   * Tests \Drupal\Core\Routing\RouteBuilder::rebuildIfNeeded() method.
   */
  public function testRebuildIfNeeded(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->willReturn(TRUE);

    $this->lock->expects($this->once())
      ->method('release')
      ->with('router_rebuild');

    $this->yamlDiscovery->expects($this->any())
      ->method('findAll')
      ->willReturn([]);

    $this->routeBuilder->setRebuildNeeded();

    // This will trigger a successful rebuild.
    $this->assertTrue($this->routeBuilder->rebuildIfNeeded());

    // This will not trigger a rebuild.
    $this->assertFalse($this->routeBuilder->rebuildIfNeeded());
  }

  /**
   * Tests routes can use alternative compiler classes.
   *
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   */
  public function testRebuildWithOverriddenRouteClass(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->willReturn(TRUE);
    $this->yamlDiscovery->expects($this->once())
      ->method('findAll')
      ->willReturn([
        'test_module' => [
          'test_route.override' => [
            'path' => '/test_route_override',
            'options' => [
              'compiler_class' => 'Class\Does\Not\Exist',
            ],
          ],
          'test_route' => [
            'path' => '/test_route',
          ],
        ],
      ]);

    $container = new ContainerBuilder();
    $container->set('test_module.route_service', new TestRouteSubscriber());

    // Test that routes can have alternative compiler classes.
    $route_collection_filled = new RouteCollection();
    $route_collection_filled->add('test_route.override', new Route('/test_route_override', [], [], ['compiler_class' => 'Class\Does\Not\Exist']));
    $route_collection_filled->add('test_route', new Route('/test_route', [], [], ['compiler_class' => RouteCompiler::class]));
    $route_build_event = new RouteBuildEvent($route_collection_filled);
    $this->dispatcher->dispatch($route_build_event, RoutingEvents::DYNAMIC)
      ->shouldBeCalled();
    $this->dispatcher->dispatch($route_build_event, RoutingEvents::ALTER)
      ->shouldBeCalled();

    $this->assertTrue($this->routeBuilder->rebuild());
  }

}

/**
 * Extends the core route builder with a setter method for the YAML discovery.
 */
class TestRouteBuilder extends RouteBuilder {

  /**
   * The mocked YAML discovery.
   *
   * @var \Drupal\Core\Discovery\YamlDiscovery|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $yamlDiscovery;

  /**
   * Sets the YAML discovery.
   *
   * @param \Drupal\Core\Discovery\YamlDiscovery $yaml_discovery
   *   The YAML discovery to set.
   */
  public function setYamlDiscovery(YamlDiscovery $yaml_discovery): void {
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
