<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber
 * @group EventSubscriber
 */
class SpecialAttributesRouteSubscriberTest extends UnitTestCase {

  /**
   * Provides a list of routes with invalid route variables.
   *
   * @return array
   *   An array of invalid routes.
   */
  public function providerTestOnRouteBuildingInvalidVariables() {
    // Build an array of mock route objects based on paths.
    $routes = [];
    $paths = [
      '/test/{system_path}',
      '/test/{_legacy}',
      '/test/{' . RouteObjectInterface::ROUTE_OBJECT . '}',
      '/test/{' . RouteObjectInterface::ROUTE_NAME . '}',
      '/test/{_content}',
      '/test/{_form}',
      '/test/{_raw_variables}',
    ];

    foreach ($paths as $path) {
      $routes[] = [new Route($path)];
    }

    return $routes;
  }

  /**
   * Provides a list of routes with valid route variables.
   *
   * @return array
   *   An array of valid routes.
   */
  public function providerTestOnRouteBuildingValidVariables() {
    // Build an array of mock route objects based on paths.
    $routes = [];
    $paths = [
      '/test/{account}',
      '/test/{node}',
      '/test/{user}',
      '/test/{entity_test}',
    ];

    foreach ($paths as $path) {
      $routes[] = [new Route($path)];
    }

    return $routes;
  }

  /**
   * Tests the onAlterRoutes method for valid variables.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check.
   *
   * @dataProvider providerTestOnRouteBuildingValidVariables
   *
   * @covers ::onAlterRoutes
   */
  public function testOnRouteBuildingValidVariables(Route $route) {
    $route_collection = new RouteCollection();
    $route_collection->add('test', $route);

    $event = new RouteBuildEvent($route_collection);
    $subscriber = new SpecialAttributesRouteSubscriber();
    $this->assertNull($subscriber->onAlterRoutes($event));
  }

  /**
   * Tests the onAlterRoutes method for invalid variables.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check.
   *
   * @dataProvider providerTestOnRouteBuildingInvalidVariables
   * @covers ::onAlterRoutes
   */
  public function testOnRouteBuildingInvalidVariables(Route $route) {
    $route_collection = new RouteCollection();
    $route_collection->add('test', $route);

    $event = new RouteBuildEvent($route_collection);
    $subscriber = new SpecialAttributesRouteSubscriber();
    $this->expectWarning();
    $this->expectWarningMessage('uses reserved variable names');
    $subscriber->onAlterRoutes($event);
  }

}
