<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Routing\RouteBuilder;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\YamlRouteDiscovery;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests Drupal\Core\Routing\RouteBuilder.
 */
#[CoversClass(RouteBuilder::class)]
#[Group('Routing')]
class RouteBuilderTest extends UnitTestCase implements EventSubscriberInterface {

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
   * The route collection that the dynamic and alter events expect to receive.
   */
  protected RouteCollection $expectedRouteCollection;

  /**
   * Indicates whether the dynamic event was fired.
   */
  protected bool $dynamicFired = FALSE;

  /**
   * Indicates whether the alter event was fired.
   */
  protected bool $alterFired = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dumper = $this->createMock('Drupal\Core\Routing\MatcherDumperInterface');
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->dispatcher = new EventDispatcher();
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->controllerResolver = $this->createMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->yamlDiscovery = $this->getMockBuilder('\Drupal\Core\Discovery\YamlDiscovery')
      ->disableOriginalConstructor()
      ->getMock();
    $this->checkProvider = $this->createMock('\Drupal\Core\Access\CheckProviderInterface');

    $yamlRouteDiscovery = new TestYamlRouteDiscovery($this->moduleHandler, $this->controllerResolver);
    $yamlRouteDiscovery->setYamlDiscovery($this->yamlDiscovery);

    $this->dispatcher->addSubscriber($this);
    $this->dispatcher->addSubscriber($yamlRouteDiscovery);
    $this->routeBuilder = new RouteBuilder($this->dumper, $this->lock, $this->dispatcher, $this->checkProvider);
  }

  /**
   * Tests that the route rebuilding both locks and unlocks.
   */
  public function testRebuildLockingUnlocking(): void {
    $this->expectedRouteCollection = new RouteCollection();
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
    $this->assertTrue($this->dynamicFired);
    $this->assertTrue($this->alterFired);
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
    $this->assertFalse($this->dynamicFired);
    $this->assertFalse($this->alterFired);
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
    $this->expectedRouteCollection = $route_collection;

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
    $this->assertTrue($this->dynamicFired);
    $this->assertTrue($this->alterFired);
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
    $this->expectedRouteCollection = $route_collection_filled;

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
    $this->assertTrue($this->dynamicFired);
    $this->assertTrue($this->alterFired);
  }

  /**
   * Tests \Drupal\Core\Routing\RouteBuilder::rebuildIfNeeded() method.
   */
  public function testRebuildIfNeeded(): void {
    $this->expectedRouteCollection = new RouteCollection();
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
    $this->assertTrue($this->dynamicFired);
    $this->assertTrue($this->alterFired);

    $this->dynamicFired = FALSE;
    $this->alterFired = FALSE;
    // This will not trigger a rebuild.
    $this->assertFalse($this->routeBuilder->rebuildIfNeeded());
    $this->assertFalse($this->dynamicFired);
    $this->assertFalse($this->alterFired);
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
    $this->expectedRouteCollection = $route_collection_filled;

    $this->assertTrue($this->routeBuilder->rebuild());
    $this->assertTrue($this->dynamicFired);
    $this->assertTrue($this->alterFired);
  }

  #[IgnoreDeprecations]
  public function testDeprecatedConstructorArgs(): void {
    $this->expectDeprecation('Calling Drupal\Core\Routing\RouteBuilder::__construct() with the module handler and controller resolver services is deprecated in drupal:11.4.0 and will be removed in drupal:12.0.0. See https://www.drupal.org/node/3324751');
    new RouteBuilder($this->dumper, $this->lock, $this->dispatcher, $this->moduleHandler, $this->controllerResolver, $this->checkProvider);
  }

  public function onRouteDynamic(RouteBuildEvent $event): void {
    $this->dynamicFired = TRUE;
    $this->assertEquals($event->getRouteCollection(), $this->expectedRouteCollection);
  }

  public function onRouteAlter(RouteBuildEvent $event): void {
    $this->alterFired = TRUE;
    $this->assertEquals($event->getRouteCollection(), $this->expectedRouteCollection);
  }

  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::DYNAMIC] = ['onRouteDynamic'];
    $events[RoutingEvents::ALTER] = ['onRouteAlter'];
    return $events;
  }

}

/**
 * Extends the core route builder with a setter method for the YAML discovery.
 */
class TestYamlRouteDiscovery extends YamlRouteDiscovery {

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

  public function routesFromArray(): array {
    return [
      'test_route.1' => new Route('/test-route/1'),
    ];
  }

  public function routesFromCollection(): RouteCollection {
    $collection = new RouteCollection();
    $collection->add('test_route.2', new Route('/test-route/2'));
    return $collection;
  }

}
