<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\LazyRouteCollection;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\Core\Routing\LazyRouteCollection.
 */
#[CoversClass(LazyRouteCollection::class)]
#[Group('Routing')]
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
   * Tests get iterator.
   *
   * @legacy-covers ::getIterator
   * @legacy-covers ::all
   */
  public function testGetIterator(): void {
    $this->routeProvider->expects($this->exactly(2))
      ->method('getRoutesByNames')
      ->with(NULL)
      ->willReturn($this->testRoutes);
    $lazyRouteCollection = new LazyRouteCollection($this->routeProvider);
    $this->assertEquals($this->testRoutes, (array) $lazyRouteCollection->getIterator());
    $this->assertEquals($this->testRoutes, $lazyRouteCollection->all());
  }

  /**
   * Tests count.
   */
  public function testCount(): void {
    $this->routeProvider
      ->method('getRoutesByNames')
      ->with(NULL)
      ->willReturn($this->testRoutes);
    $lazyRouteCollection = new LazyRouteCollection($this->routeProvider);
    $this->assertEquals(2, $lazyRouteCollection->count());
  }

  /**
   * Search for a both an existing and a non-existing route.
   */
  public function testGetName(): void {
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
