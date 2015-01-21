<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Controller\ViewAjaxControllerTest.
 */

namespace Drupal\Tests\views\Unit\Controller {

use Drupal\Tests\UnitTestCase;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views\Controller\ViewAjaxController;
use Symfony\Component\HttpFoundation\Request;


/**
 * @coversDefaultClass \Drupal\views\Controller\ViewAjaxController
 * @group views
 */
class ViewAjaxControllerTest extends UnitTestCase {

  /**
   * The mocked view entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $viewStorage;

  /**
   * The mocked executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executableFactory;

  /**
   * The tested views ajax controller.
   *
   * @var \Drupal\views\Controller\ViewAjaxController
   */
  protected $viewAjaxController;

  protected function setUp() {
    $this->viewStorage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->executableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->renderer = $this->getMock('\Drupal\Core\Render\RendererInterface');
    $this->renderer->expects($this->any())
      ->method('render')
      ->will($this->returnCallback(function(array &$elements) {
        $elements['#attached'] = [];
        return isset($elements['#markup']) ? $elements['#markup'] : '';
      }));

    $this->viewAjaxController = new ViewAjaxController($this->viewStorage, $this->executableFactory, $this->renderer);
  }

  /**
   * Tests missing view_name and view_display_id
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testMissingViewName() {
    $request = new Request();
    $this->viewAjaxController->ajaxView($request);
  }

  /**
   * Tests with view_name and view_display_id but not existing view.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function testMissingView() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');

    $this->viewStorage->expects($this->once())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue(FALSE));

    $this->viewAjaxController->ajaxView($request);
  }

  /**
   * Tests a view without having access to it.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function testAccessDeniedView() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');

    $view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();

    $this->viewStorage->expects($this->once())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue($view));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->expects($this->once())
      ->method('access')
      ->will($this->returnValue(FALSE));

    $this->executableFactory->expects($this->once())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    $this->viewAjaxController->ajaxView($request);
  }

  /**
   * Tests a valid view without arguments pagers etc.
   */
  public function testAjaxView() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');

    list($view, $executable) = $this->setupValidMocks();

    $display_handler = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    // Ensure that the pager element is not set.
    $display_handler->expects($this->never())
      ->method('setOption');

    $display_collection = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();
    $display_collection->expects($this->any())
      ->method('get')
      ->with('page_1')
      ->will($this->returnValue($display_handler));

    $executable->displayHandlers = $display_collection;

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertTrue($response instanceof ViewAjaxResponse);

    $this->assertSame($response->getView(), $executable);

    $this->assertViewResultCommand($response);
  }

  /**
   * Tests a valid view with arguments.
   */
  public function testAjaxViewWithArguments() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');
    $request->request->set('view_args', 'arg1/arg2');

    list($view, $executable) = $this->setupValidMocks();
    $executable->expects($this->once())
      ->method('preview')
      ->with('page_1', array('arg1', 'arg2'));

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertTrue($response instanceof ViewAjaxResponse);

    $this->assertViewResultCommand($response);
  }

  /**
   * Tests a valid view with arguments.
   */
  public function testAjaxViewWithEmptyArguments() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');
    // Simulate a request that has a second, empty argument.
    $request->request->set('view_args', 'arg1/');

    list($view, $executable) = $this->setupValidMocks();
    $executable->expects($this->once())
      ->method('preview')
      ->with('page_1', $this->identicalTo(array('arg1', NULL)));

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertTrue($response instanceof ViewAjaxResponse);

    $this->assertViewResultCommand($response);
  }

  /**
   * Tests a valid view with a pager.
   */
  public function testAjaxViewWithPager() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');
    $dom_id = $this->randomMachineName(20);
    $request->request->set('view_dom_id', $dom_id);
    $request->request->set('pager_element', '0');

    list($view, $executable) = $this->setupValidMocks();

    $display_handler = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $display_handler->expects($this->once())
      ->method('setOption', '0')
      ->with($this->equalTo('pager_element'));

    $display_collection = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();
    $display_collection->expects($this->any())
      ->method('get')
      ->with('page_1')
      ->will($this->returnValue($display_handler));
    $executable->displayHandlers = $display_collection;

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertTrue($response instanceof ViewAjaxResponse);

    $commands = $this->getCommands($response);
    $this->assertEquals('viewsScrollTop', $commands[0]['command']);
    $this->assertEquals('.view-dom-id-' . $dom_id, $commands[0]['selector']);

    $this->assertViewResultCommand($response, 1);
  }

  /**
   * Sets up a bunch of valid mocks like the view entity and executable.
   */
  protected function setupValidMocks() {
    $view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();

    $this->viewStorage->expects($this->once())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue($view));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->expects($this->once())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $executable->expects($this->once())
      ->method('preview')
      ->will($this->returnValue(array('#markup' => 'View result')));

    $this->executableFactory->expects($this->once())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    return array($view, $executable);
  }

  /**
   * Gets the commands entry from the response object.
   *
   * @param \Drupal\views\Ajax\ViewAjaxResponse $response
   *   The views ajax response object.
   *
   * @return mixed
   *   Returns the commands.
   */
  protected function getCommands(ViewAjaxResponse $response) {
    $reflection_property = new \ReflectionProperty('Drupal\views\Ajax\ViewAjaxResponse', 'commands');
    $reflection_property->setAccessible(TRUE);
    $commands = $reflection_property->getValue($response);
    return $commands;
  }

  /**
   * Ensures that the main view content command is added.
   *
   * @param \Drupal\views\Ajax\ViewAjaxResponse $response
   *   The response object.
   * @param int $position
   *   The position where the view content command is expected.
   */
  protected function assertViewResultCommand(ViewAjaxResponse $response, $position = 0) {
    $commands = $this->getCommands($response);
    $this->assertEquals('insert', $commands[$position]['command']);
    $this->assertEquals('View result', $commands[$position]['data']);
  }

}

}

namespace {
  // @todo Remove once drupal_get_destination is converted to autoloadable code.
  if (!function_exists('drupal_static')) {
    function &drupal_static($key) {
      return $key;
    }
  }

}
