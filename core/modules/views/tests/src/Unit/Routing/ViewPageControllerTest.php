<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Routing\ViewPageControllerTest.
 */

namespace Drupal\Tests\views\Unit\Routing;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Routing\ViewPageController;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\views\Routing\ViewPageController
 * @group views
 */
class ViewPageControllerTest extends UnitTestCase {

  /**
   * The page controller of views.
   *
   * @var \Drupal\views\Routing\ViewPageController
   */
  public $pageController;

  /**
   * The mocked view storage.
   *
   * @var \Drupal\views\ViewStorage|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * The mocked view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executableFactory;

  protected function setUp() {
    $this->storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->pageController = new ViewPageController($this->storage, $this->executableFactory);
  }

  /**
   * Tests the page controller.
   */
  public function testPageController() {
    $view = $this->getMock('Drupal\views\ViewEntityInterface');

    $this->storage->expects($this->once())
      ->method('load')
      ->with('test_page_view')
      ->will($this->returnValue($view));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->expects($this->once())
      ->method('setDisplay')
      ->with('default');
    $executable->expects($this->once())
      ->method('initHandlers');

    $views_display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $views_display->expects($this->any())
      ->method('getDefinition')
      ->willReturn([]);
    $executable->display_handler = $views_display;

    $build = [
      '#type' => 'view',
      '#name' => 'test_page_view',
      '#display_id' => 'default'
    ];
    $executable->expects($this->once())
      ->method('buildRenderable')
      ->with('default', [])
      ->will($this->returnValue($build));

    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'default');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test', ['view_id' => 'test_page_view', 'display_id' => 'default']));
    $route_match = RouteMatch::createFromRequest($request);

    $output = $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $request, $route_match);
    $this->assertInternalType('array', $output);
    $this->assertEquals($build, $output);
  }

  /**
   * Tests the page controller with arguments on a non overridden page view.
   */
  public function testHandleWithArgumentsWithoutOverridden() {
    $view = $this->getMock('Drupal\views\ViewEntityInterface');

    $this->storage->expects($this->once())
      ->method('load')
      ->with('test_page_view')
      ->will($this->returnValue($view));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->expects($this->once())
      ->method('setDisplay')
      ->with('page_1');
    $executable->expects($this->once())
      ->method('initHandlers');

    $views_display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $views_display->expects($this->any())
      ->method('getDefinition')
      ->willReturn([]);
    $executable->display_handler = $views_display;

    // Manually setup a argument handler.
    $argument = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->argument['test_id'] = $argument;

    $executable->expects($this->once())
      ->method('buildRenderable')
      ->with('page_1', array('test-argument'));

    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'page_1');
    // Add the argument to the request.
    $request->attributes->set('arg_0', 'test-argument');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test/{arg_0}', ['view_id' => 'test_page_view', 'display_id' => 'default']));
    $route_match = RouteMatch::createFromRequest($request);

    $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $request, $route_match);
  }

  /**
   * Tests the page controller with arguments of a overridden page view.
   *
   * Note: This test does not care about upcasting for now.
   */
  public function testHandleWithArgumentsOnOveriddenRoute() {
    $view = $this->getMock('Drupal\views\ViewEntityInterface');

    $this->storage->expects($this->once())
      ->method('load')
      ->with('test_page_view')
      ->will($this->returnValue($view));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->expects($this->once())
      ->method('setDisplay')
      ->with('page_1');
    $executable->expects($this->once())
      ->method('initHandlers');

    $views_display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $views_display->expects($this->any())
      ->method('getDefinition')
      ->willReturn([]);
    $executable->display_handler = $views_display;

    // Manually setup a argument handler.
    $argument = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->argument['test_id'] = $argument;

    $executable->expects($this->once())
      ->method('buildRenderable')
      ->with('page_1', array('test-argument'));

    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'page_1');
    // Add the argument to the request.
    $request->attributes->set('parameter', 'test-argument');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test/{parameter}', ['view_id' => 'test_page_view', 'display_id' => 'default'], [], ['_view_argument_map' => [
      'arg_0' => 'parameter',
    ]]));
    $route_match = RouteMatch::createFromRequest($request);

    $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $request, $route_match);
  }

  /**
   * Tests the page controller with arguments of a overridden page view.
   *
   * This test care about upcasted values and ensures that the raw variables
   * are pulled in.
   */
  public function testHandleWithArgumentsOnOveriddenRouteWithUpcasting() {
    $view = $this->getMock('Drupal\views\ViewEntityInterface');

    $this->storage->expects($this->once())
      ->method('load')
      ->with('test_page_view')
      ->will($this->returnValue($view));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->expects($this->once())
      ->method('setDisplay')
      ->with('page_1');
    $executable->expects($this->once())
      ->method('initHandlers');

    $views_display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $views_display->expects($this->any())
      ->method('getDefinition')
      ->willReturn([]);
    $executable->display_handler = $views_display;

    // Manually setup a argument handler.
    $argument = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->argument['test_id'] = $argument;

    $executable->expects($this->once())
      ->method('buildRenderable')
      ->with('page_1', array('example_id'));

    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'page_1');
    // Add the argument to the request.
    $request->attributes->set('test_entity', $this->getMock('Drupal\Core\Entity\EntityInterface'));
    $raw_variables = new ParameterBag(array('test_entity' => 'example_id'));
    $request->attributes->set('_raw_variables', $raw_variables);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test/{test_entity}', ['view_id' => 'test_page_view', 'display_id' => 'default'], [], ['_view_argument_map' => [
      'arg_0' => 'test_entity',
    ]]));
    $route_match = RouteMatch::createFromRequest($request);

    $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $request, $route_match);
  }

  /**
   * Tests handle with a non existing view.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testHandleWithNotExistingView() {
    // Pass in a non existent view.
    $random_view_id = $this->randomMachineName();

    $request = new Request();
    $request->attributes->set('view_id', $random_view_id);
    $request->attributes->set('display_id', 'default');
    $route_match = RouteMatch::createFromRequest($request);

    $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $request, $route_match);
  }

}
