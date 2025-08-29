<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber.
 */
#[CoversClass(SpecialAttributesRouteSubscriber::class)]
#[Group('EventSubscriber')]
class SpecialAttributesRouteSubscriberTest extends UnitTestCase {

  /**
   * Provides a list of routes with invalid route variables.
   *
   * @return array
   *   An array of invalid routes.
   */
  public static function providerTestOnRouteBuildingInvalidVariables() {
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
  public static function providerTestOnRouteBuildingValidVariables() {
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
   * @legacy-covers ::onAlterRoutes
   */
  #[DataProvider('providerTestOnRouteBuildingValidVariables')]
  public function testOnRouteBuildingValidVariables(Route $route): void {
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
   * @legacy-covers ::onAlterRoutes
   */
  #[DataProvider('providerTestOnRouteBuildingInvalidVariables')]
  public function testOnRouteBuildingInvalidVariables(Route $route): void {
    $route_collection = new RouteCollection();
    $route_collection->add('test', $route);

    $event = new RouteBuildEvent($route_collection);
    $subscriber = new SpecialAttributesRouteSubscriber();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Route test uses reserved variable names:');
    $subscriber->onAlterRoutes($event);
  }

}
