<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\LazyRouteCollection;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Routing\LazyRouteCollection
 *
 * @group Routing
 */
class LazyRouteCollectionTest extends UnitTestCase {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  private $routeProvider;

  /**
   * Array of routes indexed by name.
   *
   * @var array
   */
  private $testRoutes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->routeProvider = $this->createMock(RouteProviderInterface::class);
    $this->testRoutes = [
      'route_1' => new Route('/route-1'),
      'route_2' => new Route('/route-2'),
    ];
  }

  /**
   * @covers ::getIterator
   * @covers ::all
   */
  public function testGetIterator() {
    $this->routeProvider->expects($this->exactly(2))
      ->method('getRoutesByNames')
      ->with(NULL)
      ->willReturn($this->testRoutes);
    $lazyRouteCollection = new LazyRouteCollection($this->routeProvider);
    $this->assertEquals($this->testRoutes, (array) $lazyRouteCollection->getIterator());
    $this->assertEquals($this->testRoutes, $lazyRouteCollection->all());
  }

  /**
   * @covers ::count
   */
  public function testCount() {
    $this->routeProvider
      ->method('getRoutesByNames')
      ->with(NULL)
      ->willReturn($this->testRoutes);
    $lazyRouteCollection = new LazyRouteCollection($this->routeProvider);
    $this->assertEquals(2, $lazyRouteCollection->count());
  }

  /**
   * Search for a both an existing and a non-existing route.
   *
   * @covers ::get
   */
  public function testGetName() {
    // Hit.
    $this->routeProvider
      ->method('getRouteByName')
      ->with('route_1')
      ->willReturn($this->testRoutes['route_1']);
    $lazyRouteCollection = new LazyRouteCollection($this->routeProvider);
    $this->assertEquals($lazyRouteCollection->get('route_1'), $this->testRoutes['route_1']);

    // Miss.
    $this->routeProvider
      ->method('getRouteByName')
      ->with('does_not_exist')
      ->will($this->throwException(new RouteNotFoundException()));

    $lazyRouteCollectionFail = new LazyRouteCollection($this->routeProvider);
    $this->assertNull($lazyRouteCollectionFail->get('does_not_exist'));
  }

}
