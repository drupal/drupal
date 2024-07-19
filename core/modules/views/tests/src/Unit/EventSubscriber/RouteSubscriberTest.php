<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\views\EventSubscriber\RouteSubscriber;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\views\EventSubscriber\RouteSubscriber
 * @group views
 */
class RouteSubscriberTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked config entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $viewStorage;

  /**
   * The tested views route subscriber.
   *
   * @var \Drupal\views\EventSubscriber\RouteSubscriber|\Drupal\Tests\views\Unit\EventSubscriber\TestRouteSubscriber
   */
  protected $routeSubscriber;

  /**
   * The mocked key value storage.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->viewStorage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('view')
      ->willReturn($this->viewStorage);
    $this->state = $this->createMock('\Drupal\Core\State\StateInterface');
    $this->routeSubscriber = new TestRouteSubscriber($this->entityTypeManager, $this->state);
  }

  /**
   * @covers ::routeRebuildFinished
   */
  public function testRouteRebuildFinished(): void {
    [$display_1, $display_2] = $this->setupMocks();

    $display_1->expects($this->once())
      ->method('collectRoutes')
      ->willReturn(['test_id.page_1' => 'views.test_id.page_1']);
    $display_2->expects($this->once())
      ->method('collectRoutes')
      ->willReturn(['test_id.page_2' => 'views.test_id.page_2']);

    $this->routeSubscriber->routes();

    $this->state->expects($this->once())
      ->method('set')
      ->with('views.view_route_names', ['test_id.page_1' => 'views.test_id.page_1', 'test_id.page_2' => 'views.test_id.page_2']);
    $this->routeSubscriber->routeRebuildFinished();
  }

  /**
   * Tests the onAlterRoutes method.
   *
   * @see \Drupal\views\EventSubscriber\RouteSubscriber::onAlterRoutes()
   */
  public function testOnAlterRoutes(): void {
    $collection = new RouteCollection();
    // The first route will be overridden later.
    $collection->add('test_route', new Route('test_route', ['_controller' => 'Drupal\Tests\Core\Controller\TestController']));
    $route_2 = new Route('test_route/example', ['_controller' => 'Drupal\Tests\Core\Controller\TestController']);
    $collection->add('test_route_2', $route_2);

    $route_event = new RouteBuildEvent($collection);

    [$display_1, $display_2] = $this->setupMocks();

    // The page_1 display overrides an existing route, so the dynamicRoutes
    // should only call the second display.
    $display_1->expects($this->once())
      ->method('collectRoutes')
      ->willReturnCallback(function () use ($collection) {
        $collection->add('views.test_id.page_1', new Route('test_route', ['_controller' => 'Drupal\views\Routing\ViewPageController']));
        return ['test_id.page_1' => 'views.test_id.page_1'];
      });
    $display_1->expects($this->once())
      ->method('alterRoutes')
      ->willReturn(['test_id.page_1' => 'test_route']);

    $display_2->expects($this->once())
      ->method('collectRoutes')
      ->willReturnCallback(function () use ($collection) {
        $collection->add('views.test_id.page_2', new Route('test_route', ['_controller' => 'Drupal\views\Routing\ViewPageController']));
        return ['test_id.page_2' => 'views.test_id.page_2'];
      });
    $display_2->expects($this->once())
      ->method('alterRoutes')
      ->willReturn([]);

    // Ensure that even both the collectRoutes() and alterRoutes() methods
    // are called on the displays, we ensure that the route first defined by
    // views is dropped.

    $this->routeSubscriber->routes();
    $this->assertNull($this->routeSubscriber->onAlterRoutes($route_event));

    $this->state->expects($this->once())
      ->method('set')
      ->with('views.view_route_names', ['test_id.page_1' => 'test_route', 'test_id.page_2' => 'views.test_id.page_2']);

    $collection = $route_event->getRouteCollection();
    $this->assertEquals(['test_route', 'test_route_2', 'views.test_id.page_2'], array_keys($collection->all()));

    $this->routeSubscriber->routeRebuildFinished();
  }

  /**
   * Sets up mocks of Views objects needed for testing.
   *
   * @return \Drupal\views\Plugin\views\display\DisplayRouterInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
   *   An array of two mocked view displays.
   */
  protected function setupMocks(): array {
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $this->viewStorage->expects($this->any())
      ->method('load')
      ->willReturn($view);

    $view->expects($this->any())
      ->method('getExecutable')
      ->willReturn($executable);
    $view->expects($this->any())
      ->method('id')
      ->willReturn('test_id');
    $executable->storage = $view;

    $executable->expects($this->any())
      ->method('setDisplay')
      ->willReturnMap([
        ['page_1', TRUE],
        ['page_2', TRUE],
        ['page_3', FALSE],
      ]);

    // Ensure that only the first two displays are actually called.
    $display_1 = $this->createMock('Drupal\views\Plugin\views\display\DisplayRouterInterface');
    $display_2 = $this->createMock('Drupal\views\Plugin\views\display\DisplayRouterInterface');

    $display_collection = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();
    $display_collection->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['page_1', $display_1],
        ['page_2', $display_2],
      ]);
    $executable->displayHandlers = $display_collection;

    $this->routeSubscriber->applicableViews = [];
    $this->routeSubscriber->applicableViews[] = ['test_id', 'page_1'];
    $this->routeSubscriber->applicableViews[] = ['test_id', 'page_2'];
    $this->routeSubscriber->applicableViews[] = ['test_id', 'page_3'];

    return [$display_1, $display_2];
  }

}

/**
 * Provides a test route subscriber.
 */
class TestRouteSubscriber extends RouteSubscriber {

  /**
   * The applicable views.
   *
   * @var array
   */
  public $applicableViews;

  /**
   * {@inheritdoc}
   */
  protected function getApplicableViews() {
    return $this->applicableViews;
  }

}
