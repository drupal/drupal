<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\EventSubscriber\ModuleRouteSubscriberTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\EventSubscriber\ModuleRouteSubscriber;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Tests the ModuleRouteSubscriber class.
 *
 * @group Drupal
 *
 * @see \Drupal\Core\EventSubscriber\ModuleRouteSubscriber
 */
class ModuleRouteSubscriberTest extends UnitTestCase {

  /**
   * The mock module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  public static function getInfo() {
    return array(
      'name' => 'Module route subscriber',
      'description' => 'Unit test the \Drupal\Core\EventSubscriber\ModuleRouteSubscriber class.',
      'group' => 'System'
    );
  }

  public function setUp() {
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $value_map = array(
      array('enabled', TRUE),
      array('disabled', FALSE),
    );

    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->will($this->returnValueMap($value_map));
  }

  /**
   * Tests that removeRoute() removes routes when the module is not enabled.
   *
   * @dataProvider testRemoveRouteProvider
   *
   * @param string $route_name
   *   The machine name for the route.
   * @param array $requirements
   *   An array of requirements to use for the route.
   * @param bool $removed
   *   Whether or not the route is expected to be removed from the collection.
   */
  public function testRemoveRoute($route_name, array $requirements, $removed) {
    $collection = new RouteCollection();
    $route = new Route('', array(), $requirements);
    $collection->add($route_name, $route);

    $event = new RouteBuildEvent($collection, 'test');
    $route_subscriber = new ModuleRouteSubscriber($this->moduleHandler);
    $route_subscriber->onAlterRoutes($event);

    if ($removed) {
      $this->assertNull($collection->get($route_name));
    }
    else {
      $this->assertInstanceOf('Symfony\Component\Routing\Route', $collection->get($route_name));
    }
  }

  /**
   * Data provider for testRemoveRoute().
   */
  public function testRemoveRouteProvider() {
    return array(
      array('enabled', array('_module_dependencies' => 'enabled'), FALSE),
      array('disabled', array('_module_dependencies' => 'disabled'), TRUE),
      array('enabled_or',  array('_module_dependencies' => 'disabled,enabled'), FALSE),
      array('enabled_or',  array('_module_dependencies' => 'enabled,disabled'), FALSE),
      array('disabled_or',  array('_module_dependencies' => 'disabled,disabled'), TRUE),
      array('enabled_and',  array('_module_dependencies' => 'enabled+enabled'), FALSE),
      array('enabled_and',  array('_module_dependencies' => 'enabled+disabled'), TRUE),
      array('enabled_and',  array('_module_dependencies' => 'disabled+enabled'), TRUE),
      array('disabled_and',  array('_module_dependencies' => 'disabled+disabled'), TRUE),
      array('no_dependencies', array(), FALSE),
    );
  }

}
