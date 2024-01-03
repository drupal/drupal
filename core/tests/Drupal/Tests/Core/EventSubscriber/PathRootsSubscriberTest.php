<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\PathRootsSubscriber;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\PathRootsSubscriber
 * @group EventSubscriber
 */
class PathRootsSubscriberTest extends UnitTestCase {

  /**
   * The mocked state.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * The tested path root subscriber.
   *
   * @var \Drupal\Core\EventSubscriber\PathRootsSubscriber
   */
  protected $pathRootsSubscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->createMock('Drupal\Core\State\StateInterface');
    $this->pathRootsSubscriber = new PathRootsSubscriber($this->state);
  }

  /**
   * Tests altering and finished event.
   *
   * @covers ::onRouteAlter
   * @covers ::onRouteFinished
   */
  public function testSubscribing() {

    // Ensure that onRouteFinished can be called without throwing notices
    // when no path roots got set.
    $this->pathRootsSubscriber->onRouteFinished();

    $route_collection = new RouteCollection();
    $route_collection->add('test_route1', new Route('/test/bar'));
    $route_collection->add('test_route2', new Route('/test/baz'));
    $route_collection->add('test_route3', new Route('/test2/bar/baz'));

    $event = new RouteBuildEvent($route_collection);
    $this->pathRootsSubscriber->onRouteAlter($event);

    $route_collection = new RouteCollection();
    $route_collection->add('test_route4', new Route('/test1/bar'));
    $route_collection->add('test_route5', new Route('/test2/baz'));
    $route_collection->add('test_route6', new Route('/test2/bar/baz'));

    $event = new RouteBuildEvent($route_collection);
    $this->pathRootsSubscriber->onRouteAlter($event);

    $this->state->expects($this->once())
      ->method('set')
      ->with('router.path_roots', ['test', 'test2', 'test1']);

    $this->pathRootsSubscriber->onRouteFinished();
  }

}
