<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Controller\ViewAjaxControllerTest.
 */

namespace Drupal\views\Tests\Controller {

use Drupal\Tests\UnitTestCase;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views\Controller\ViewAjaxController;
use Symfony\Component\HttpFoundation\Request;


/**
 * Tests the views ajax controller.
 *
 * @group Drupal
 * @group Views
 *
 * @see \Drupal\views\Controller\ViewAjaxController
 */
class ViewAjaxControllerTest extends UnitTestCase {

  /**
   * The mocked view entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface|\PHPUnit_Framework_MockObject_MockObject
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

  public static function getInfo() {
    return array(
      'name' => 'View: Ajax controller',
      'description' => 'Tests the views ajax controller.',
      'group' => 'Views'
    );
  }

  protected function setUp() {
    $this->viewStorage = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');
    $this->executableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->viewAjaxController = new ViewAjaxController($this->viewStorage, $this->executableFactory);
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

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertTrue($response instanceof ViewAjaxResponse);

    $this->assertSame($response->getView(), $executable);
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

}

}

namespace {
  // @todo Remove once drupal_get_destination is converted to autoloadable code.
  if (!function_exists('drupal_static')) {
    function &drupal_static($key) {
      return $key;
    }
  }

  // @todo Remove once drupal_render is converted to autoloadable code.
  if (!function_exists('drupal_render')) {
    function drupal_render($array) {
      return isset($array['#markup']) ? $array['#markup'] : '';
    }
  }

}
