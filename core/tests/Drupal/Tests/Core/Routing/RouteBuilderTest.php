<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\RouteBuilderTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Routing\RouteBuilder;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\system\Tests\Routing\RoutingFixtures;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the route builder.
 *
 * @group Drupal
 * @group Routing
 *
 * @see \Drupal\Core\Routing\RouteBuilder
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
   * The mocked yaml discovery.
   *
   * @var \Drupal\Component\Discovery\YamlDiscovery|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $yamlDiscovery;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public static function getInfo() {
    return array(
      'name' => 'Route Builder',
      'description' => 'Tests the route builder.',
      'group' => 'Routing',
    );
  }

  protected function setUp() {
    $this->dumper = $this->getMock('Drupal\Core\Routing\MatcherDumperInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->dispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->yamlDiscovery = $this->getMockBuilder('\Drupal\Component\Discovery\YamlDiscovery')
      ->disableOriginalConstructor()
      ->getMock();

    $this->routeBuilder = new TestRouteBuilder($this->dumper, $this->lock, $this->dispatcher, $this->moduleHandler);
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
      ->will($this->returnValue(array()));

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
      ->will($this->returnValue(array('test_module' => $routes)));

    // Ensure that the dispatch events for altering are fired.
    $this->dispatcher->expects($this->at(0))
      ->method('dispatch')
      ->with($this->equalTo(RoutingEvents::ALTER), $this->isInstanceOf('Drupal\Core\Routing\RouteBuildEvent'));

    $empty_collection = new RouteCollection();
    $route_build_event = new RouteBuildEvent($empty_collection, 'dynamic_routes');

    // Ensure that the dynamic routes events are fired.
    $this->dispatcher->expects($this->at(1))
      ->method('dispatch')
      ->with(RoutingEvents::DYNAMIC, $route_build_event);

    $this->dispatcher->expects($this->at(2))
      ->method('dispatch')
      ->with(RoutingEvents::ALTER, $route_build_event);

    // Ensure that the routes are set to the dumper and dumped.
    $this->dumper->expects($this->at(0))
      ->method('addRoutes')
      ->with($routing_fixtures->sampleRouteCollection());
    $this->dumper->expects($this->at(1))
      ->method('dump')
      ->with(array('route_set' => 'test_module'));
    $this->dumper->expects($this->at(2))
      ->method('addRoutes')
      ->with($empty_collection);
    $this->dumper->expects($this->at(3))
      ->method('dump')
      ->with(array('route_set' => 'dynamic_routes'));


    $this->assertTrue($this->routeBuilder->rebuild());
  }

  /**
   * Tests the rebuild with some dynamic routes.
   *
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   */
  public function testRebuildWithDynamicRoutes() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('router_rebuild')
      ->will($this->returnValue(TRUE));

    $this->yamlDiscovery->expects($this->once())
      ->method('findAll')
      ->will($this->returnValue(array()));

    $route_collection_filled = new RouteCollection();
    $route_collection_filled->add('test_route', new Route('/test-route'));

    $this->dispatcher->expects($this->at(0))
      ->method('dispatch')
      ->with($this->equalTo(RoutingEvents::DYNAMIC))
      ->will($this->returnCallback(function ($object, RouteBuildEvent $event) {
        $event->getRouteCollection()->add('test_route', new Route('/test-route'));
      }));

    $this->dispatcher->expects($this->at(1))
      ->method('dispatch')
      ->with($this->equalTo(RoutingEvents::ALTER));

    $this->dumper->expects($this->once())
      ->method('addRoutes')
      ->with($route_collection_filled);
    $this->dumper->expects($this->once())
      ->method('dump')
      ->with(array('route_set' => 'dynamic_routes'));

    $this->assertTrue($this->routeBuilder->rebuild());
  }

}

/**
 * Extends the core route builder with a setter method for the yaml discovery.
 */
class TestRouteBuilder extends RouteBuilder {

  /**
   * Sets the yaml discovery.
   *
   * @param \Drupal\Component\Discovery\YamlDiscovery $yaml_discovery
   *   The yaml discovery to set.
   */
  public function setYamlDiscovery(YamlDiscovery $yaml_discovery) {
    $this->yamlDiscovery = $yaml_discovery;
  }

}
