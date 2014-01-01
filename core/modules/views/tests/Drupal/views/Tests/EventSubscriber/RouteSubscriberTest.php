<?php

/**
 * @file
 * Contains \Drupal\views\Tests\EventSubscriber\RouteSubscriberTest.
 */

namespace Drupal\views\Tests\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\views\EventSubscriber\RouteSubscriber;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the views route subscriber.
 *
 * @see \Drupal\views\EventSubscriber\RouteSubscriber
 */
class RouteSubscriberTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked view storage controller.
   *
   * @var \Drupal\views\ViewStorageController|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $viewStorageController;

  /**
   * The tested views route subscriber.
   *
   * @var \Drupal\views\EventSubscriber\RouteSubscriber|\Drupal\views\Tests\EventSubscriber\TestRouteSubscriber
   */
  protected $routeSubscriber;

  /**
   * The mocked key value storage.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Views route subscriber',
      'description' => 'Tests the views route subscriber.',
      'group' => 'Views plugins',
    );
  }

  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->viewStorageController = $this->getMockBuilder('\Drupal\views\ViewStorageController')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityManager->expects($this->any())
      ->method('getStorageController')
      ->with('view')
      ->will($this->returnValue($this->viewStorageController));
    $this->state = $this->getMock('\Drupal\Core\KeyValueStore\StateInterface');
    $this->routeSubscriber = new TestRouteSubscriber($this->entityManager, $this->state);
  }

  /**
   * Tests the onDynamicRoutes method.
   *
   * @see \Drupal\views\EventSubscriber\RouteSubscriber::onDynamicRoutes()
   */
  public function testDynamicRoutes() {
    list($view, $executable, $display_1, $display_2) = $this->setupMocks();

    $display_1->expects($this->once())
      ->method('collectRoutes')
      ->will($this->returnValue(array('test_id.page_1' => 'views.test_id.page_1')));
    $display_2->expects($this->once())
      ->method('collectRoutes')
      ->will($this->returnValue(array('test_id.page_2' => 'views.test_id.page_2')));

    $this->routeSubscriber->routes();

    $this->state->expects($this->once())
      ->method('set')
      ->with('views.view_route_names', array('test_id.page_1' => 'views.test_id.page_1', 'test_id.page_2' => 'views.test_id.page_2'));
    $this->routeSubscriber->routeRebuildFinished();
  }

  /**
   * Tests the onAlterRoutes method.
   *
   * @see \Drupal\views\EventSubscriber\RouteSubscriber::onAlterRoutes()
   */
  public function testOnAlterRoutes() {
    $collection = new RouteCollection();
    $collection->add('test_route', new Route('test_route', array('_controller' => 'Drupal\Tests\Core\Controller\TestController')));
    $route_2 = new Route('test_route/example', array('_controller' => 'Drupal\Tests\Core\Controller\TestController'));
    $collection->add('test_route_2', $route_2);

    $route_event = new RouteBuildEvent($collection, 'views');

    list($view, $executable, $display_1, $display_2) = $this->setupMocks();

    // The page_1 display overrides an existing route, so the dynamicRoutes
    // should only call the second display.
    $display_1->expects($this->once())
      ->method('alterRoutes')
      ->will($this->returnValue(array('test_id.page_1' => 'test_route')));
    $display_1->expects($this->never())
      ->method('collectRoutes');

    $display_2->expects($this->once())
      ->method('alterRoutes')
      ->will($this->returnValue(array()));
    $display_2->expects($this->once())
      ->method('collectRoutes')
      ->will($this->returnValue(array('test_id.page_2' => 'views.test_id.page_2')));

    $this->assertNull($this->routeSubscriber->onAlterRoutes($route_event));

    // Ensure that after the alterRoutes the collectRoutes method is just called
    // once (not for page_1 anymore).

    $this->routeSubscriber->routes();

    $this->state->expects($this->once())
      ->method('set')
      ->with('views.view_route_names', array('test_id.page_1' => 'test_route', 'test_id.page_2' => 'views.test_id.page_2'));
    $this->routeSubscriber->routeRebuildFinished();
  }

  /**
   * Sets up mocks of Views objects needed for testing.
   *
   * @return array
   *   An array of Views mocks, including the executable, the view entity, and
   *   two display plugins.
   */
  protected function setupMocks() {
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $this->viewStorageController->expects($this->any())
      ->method('load')
      ->will($this->returnValue($view));

    $view->expects($this->any())
      ->method('getExecutable')
      ->will($this->returnValue($executable));
    $view->expects($this->any())
      ->method('id')
      ->will($this->returnValue('test_id'));
    $executable->storage = $view;

    $executable->expects($this->any())
      ->method('setDisplay')
      ->will($this->returnValueMap(array(
        array('page_1', TRUE),
        array('page_2', TRUE),
        array('page_3', FALSE),
      )));

    // Ensure that only the first two displays are actually called.
    $display_1 = $this->getMock('Drupal\views\Plugin\views\display\DisplayRouterInterface');
    $display_2 = $this->getMock('Drupal\views\Plugin\views\display\DisplayRouterInterface');

    $display_bag = $this->getMockBuilder('Drupal\views\DisplayBag')
      ->disableOriginalConstructor()
      ->getMock();
    $display_bag->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap(array(
        array('page_1', $display_1),
        array('page_2', $display_2),
      )));
    $executable->displayHandlers = $display_bag;

    $this->routeSubscriber->applicableViews = array();
    $this->routeSubscriber->applicableViews[] = array($executable, 'page_1');
    $this->routeSubscriber->applicableViews[] = array($executable, 'page_2');
    $this->routeSubscriber->applicableViews[] = array($executable, 'page_3');

    return array($executable, $view, $display_1, $display_2);
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
