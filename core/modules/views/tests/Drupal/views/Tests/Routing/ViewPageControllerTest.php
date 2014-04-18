<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Routing\ViewPageControllerTest.
 */

namespace Drupal\views\Tests\Routing;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Routing\ViewPageController;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\Routing\Route;

/**
 * Tests the page controller but not the actual execution/rendering of a view.
 *
 * @group Drupal
 * @group Views
 *
 * @see \Drupal\views\Routing\ViewPageController
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

  public static function getInfo() {
    return array(
      'name' => 'View page controller test',
      'description' => 'Tests views page controller.',
      'group' => 'Views'
    );
  }

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
    $view = $this->getMock('Drupal\views\ViewStorageInterface');

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
    $executable->expects($this->once())
      ->method('executeDisplay')
      ->with('default', array())
      ->will($this->returnValue(array('#markup' => 'example output')));

    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'default');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route(''));

    $output = $this->pageController->handle($request);
    $this->assertInternalType('array', $output);
    $this->assertEquals(array('#markup' => 'example output'), $output);
  }

  /**
   * Tests the page controller with arguments on a non overridden page view.
   */
  public function testHandleWithArgumentsWithoutOverridden() {
    $view = $this->getMock('Drupal\views\ViewStorageInterface');

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

    // Manually setup a argument handler.
    $argument = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->argument['test_id'] = $argument;

    $executable->expects($this->once())
      ->method('executeDisplay')
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
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route(''));

    $this->pageController->handle($request);
  }

  /**
   * Tests the page controller with arguments of a overridden page view.
   *
   * Note: This test does not care about upcasting for now.
   */
  public function testHandleWithArgumentsOnOveriddenRoute() {
    $view = $this->getMock('Drupal\views\ViewStorageInterface');

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

    // Manually setup a argument handler.
    $argument = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->argument['test_id'] = $argument;

    $executable->expects($this->once())
      ->method('executeDisplay')
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
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('', array(), array(), array('_view_argument_map' => array(
      'arg_0' => 'parameter',
    ))));

    $this->pageController->handle($request);
  }

  /**
   * Tests the page controller with arguments of a overridden page view.
   *
   * This test care about upcasted values and ensures that the raw variables
   * are pulled in.
   */
  public function testHandleWithArgumentsOnOveriddenRouteWithUpcasting() {
    $view = $this->getMock('Drupal\views\ViewStorageInterface');

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

    // Manually setup a argument handler.
    $argument = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->argument['test_id'] = $argument;

    $executable->expects($this->once())
      ->method('executeDisplay')
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

    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('', array(), array(), array('_view_argument_map' => array(
      'arg_0' => 'test_entity',
    ))));

    $this->pageController->handle($request);
  }

  /**
   * Tests handle with a non existing view.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testHandleWithNotExistingView() {
    // Pass in a non existent view.
    $random_view_id = $this->randomName();

    $request = new Request();
    $request->attributes->set('view_id', $random_view_id);
    $request->attributes->set('display_id', 'default');
    $this->pageController->handle($request);
  }

}
