<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\EventSubscriber\SpecialAttributesRouteSubscriberTest.
 */

namespace Drupal\Tests\Core\EventSubscriber {

use Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the special attributes route subscriber.
 *
 * @see \Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber
 */
class SpecialAttributesRouteSubscriberTest extends UnitTestCase {

  /**
   * The tested route subscriber.
   *
   * @var \Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber
   */
  protected  $specialAttributesRouteSubscriber;

  public static function getInfo() {
    return array(
      'name' => 'Special attributes route subscriber',
      'description' => 'Tests the special attributes route subscriber.',
      'group' => 'System'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->specialAttributesRouteSubscriber = new SpecialAttributesRouteSubscriber();
  }

  /**
   * Provides a list of routes with invalid route variables.
   *
   * @return array
   *   An array of invalid routes.
   */
  public function providerTestOnRouteBuildingInvalidVariables() {
    $routes = array();
    $routes[] = array(new Route('/test/{system_path}'));
    $routes[] = array(new Route('/test/{_maintenance}'));
    $routes[] = array(new Route('/test/{_legacy}'));
    $routes[] = array(new Route('/test/{_authentication_provider}'));
    $routes[] = array(new Route('/test/{' . RouteObjectInterface::ROUTE_OBJECT . '}'));
    $routes[] = array(new Route('/test/{' . RouteObjectInterface::ROUTE_NAME . '}'));
    $routes[] = array(new Route('/test/{_content}'));
    $routes[] = array(new Route('/test/{_form}'));
    $routes[] = array(new Route('/test/{_raw_variables}'));

    return $routes;
  }

  /**
   * Provides a list of routes with valid route variables.
   *
   * @return array
   *   An array of valid routes.
   */
  public function providerTestOnRouteBuildingValidVariables() {
    $routes = array();
    $routes[] = array(new Route('/test/{account}'));
    $routes[] = array(new Route('/test/{node}'));
    $routes[] = array(new Route('/test/{user}'));
    $routes[] = array(new Route('/test/{entity_test}'));

    return $routes;
  }

  /**
   * Tests the onAlterRoutes method for valid variables.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check.
   *
   * @dataProvider providerTestOnRouteBuildingValidVariables
   */
  public function testOnRouteBuildingValidVariables(Route $route) {
    $route_collection = new RouteCollection();
    $route_collection->add('test', $route);
    $event = new RouteBuildEvent($route_collection, 'test');
    $this->assertTrue($this->specialAttributesRouteSubscriber->onAlterRoutes($event));
  }

  /**
   * Tests the onAlterRoutes method for invalid variables.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check.
   *
   * @dataProvider providerTestOnRouteBuildingInvalidVariables
   */
  public function testOnRouteBuildingInvalidVariables(Route $route) {
    $route_collection = new RouteCollection();
    $route_collection->add('test', $route);
    $event = new RouteBuildEvent($route_collection, 'test');
    $this->assertFalse($this->specialAttributesRouteSubscriber->onAlterRoutes($event));
  }

}

}

namespace {
  if (!function_exists('watchdog')) {
    function watchdog($type, $message, array $args = NULL) {
    }
  }
  if (!function_exists('drupal_set_message')) {
    function drupal_set_message($type = NULL, $message = '') {
    }
  }
}
