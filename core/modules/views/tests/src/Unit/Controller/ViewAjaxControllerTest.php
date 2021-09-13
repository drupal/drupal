<?php

namespace Drupal\Tests\views\Unit\Controller;

use Drupal\Core\Render\RenderContext;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views\Controller\ViewAjaxController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\views\Controller\ViewAjaxController
 * @group views
 */
class ViewAjaxControllerTest extends UnitTestCase {

  const USE_AJAX = TRUE;
  const USE_NO_AJAX = FALSE;

  /**
   * The mocked view entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $viewStorage;

  /**
   * The mocked executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executableFactory;

  /**
   * The tested views ajax controller.
   *
   * @var \Drupal\views\Controller\ViewAjaxController
   */
  protected $viewAjaxController;

  /**
   * The mocked current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentPath;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $redirectDestination;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->viewStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->executableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $this->renderer->expects($this->any())
      ->method('render')
      ->willReturnCallback(function (array &$elements) {
        $elements['#attached'] = [];

        return isset($elements['#markup']) ? $elements['#markup'] : '';
      });
    $this->renderer->expects($this->any())
      ->method('executeInRenderContext')
      ->willReturnCallback(function (RenderContext $context, callable $callable) {
        return $callable();
      });
    $this->currentPath = $this->getMockBuilder('Drupal\Core\Path\CurrentPathStack')
      ->disableOriginalConstructor()
      ->getMock();
    $this->redirectDestination = $this->createMock('\Drupal\Core\Routing\RedirectDestinationInterface');

    $this->viewAjaxController = new ViewAjaxController($this->viewStorage, $this->executableFactory, $this->renderer, $this->currentPath, $this->redirectDestination);

    $element_info_manager = $this->createMock('\Drupal\Core\Render\ElementInfoManagerInterface');
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $args = [
      $this->createMock('\Drupal\Core\Controller\ControllerResolverInterface'),
      $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface'),
      $element_info_manager,
      $this->createMock('\Drupal\Core\Render\PlaceholderGeneratorInterface'),
      $this->createMock('\Drupal\Core\Render\RenderCacheInterface'),
      $request_stack,
      [
        'required_cache_contexts' => [
          'languages:language_interface',
          'theme',
        ],
      ],
    ];
    $this->renderer = $this->getMockBuilder('Drupal\Core\Render\Renderer')
      ->setConstructorArgs($args)
      ->onlyMethods([])
      ->getMock();
    $container = new ContainerBuilder();
    $container->set('renderer', $this->renderer);
    \Drupal::setContainer($container);
  }

  /**
   * Tests missing view_name and view_display_id.
   */
  public function testMissingViewName() {
    $request = new Request();
    $this->expectException(NotFoundHttpException::class);
    $this->viewAjaxController->ajaxView($request);
  }

  /**
   * Tests non-existent view with view_name and view_display_id.
   */
  public function testMissingView() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');

    $this->viewStorage->expects($this->once())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue(FALSE));

    $this->expectException(NotFoundHttpException::class);
    $this->viewAjaxController->ajaxView($request);
  }

  /**
   * Tests a view without having access to it.
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

    $this->expectException(AccessDeniedHttpException::class);
    $this->viewAjaxController->ajaxView($request);
  }

  /**
   * Tests a valid view without arguments pagers etc.
   */
  public function testAjaxView() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');
    $request->request->set('view_path', '/test-page');
    $request->request->set('_wrapper_format', 'ajax');
    $request->request->set('ajax_page_state', 'drupal.settings[]');
    $request->request->set('type', 'article');

    list($view, $executable) = $this->setupValidMocks();

    $this->redirectDestination->expects($this->atLeastOnce())
      ->method('set')
      ->with('/test-page?type=article');
    $this->currentPath->expects($this->once())
      ->method('setPath')
      ->with('/test-page', $request);

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertTrue($response instanceof ViewAjaxResponse);

    $this->assertSame($response->getView(), $executable);

    $this->assertViewResultCommand($response);
  }

  /**
   * Tests a valid view with a view_path with no slash.
   */
  public function testAjaxViewViewPathNoSlash() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');
    $request->request->set('view_path', 'test-page');
    $request->request->set('_wrapper_format', 'ajax');
    $request->request->set('ajax_page_state', 'drupal.settings[]');
    $request->request->set('type', 'article');

    list($view, $executable) = $this->setupValidMocks();

    $this->redirectDestination->expects($this->atLeastOnce())
      ->method('set')
      ->with('test-page?type=article');
    $this->currentPath->expects($this->once())
      ->method('setPath')
      ->with('/test-page');

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertInstanceOf(ViewAjaxResponse::class, $response);

    $this->assertSame($response->getView(), $executable);

    $this->assertViewResultCommand($response);
  }

  /**
   * Tests a valid view without ajax enabled.
   */
  public function testAjaxViewWithoutAjax() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');
    $request->request->set('view_path', '/test-page');
    $request->request->set('_wrapper_format', 'ajax');
    $request->request->set('ajax_page_state', 'drupal.settings[]');
    $request->request->set('type', 'article');

    $this->setupValidMocks(static::USE_NO_AJAX);

    $this->expectException(AccessDeniedHttpException::class);
    $this->viewAjaxController->ajaxView($request);
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
      ->with('page_1', ['arg1', 'arg2']);

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertInstanceOf(ViewAjaxResponse::class, $response);

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
      ->with('page_1', $this->identicalTo(['arg1', NULL]));

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertInstanceOf(ViewAjaxResponse::class, $response);

    $this->assertViewResultCommand($response);
  }

  /**
   * Tests a valid view with arguments.
   */
  public function testAjaxViewWithHtmlEntityArguments() {
    $request = new Request();
    $request->request->set('view_name', 'test_view');
    $request->request->set('view_display_id', 'page_1');
    $request->request->set('view_args', 'arg1 &amp; arg2/arg3');

    list($view, $executable) = $this->setupValidMocks();
    $executable->expects($this->once())
      ->method('preview')
      ->with('page_1', ['arg1 & arg2', 'arg3']);

    $response = $this->viewAjaxController->ajaxView($request);
    $this->assertInstanceOf(ViewAjaxResponse::class, $response);

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
    $this->assertInstanceOf(ViewAjaxResponse::class, $response);

    $commands = $this->getCommands($response);
    $this->assertEquals('viewsScrollTop', $commands[0]['command']);
    $this->assertEquals('.js-view-dom-id-' . $dom_id, $commands[0]['selector']);

    $this->assertViewResultCommand($response, 1);
  }

  /**
   * Sets up a bunch of valid mocks like the view entity and executable.
   *
   * @param bool $use_ajax
   *   Whether the 'use_ajax' option is set on the view display. Defaults to
   *   using ajax (TRUE).
   *
   * @return array
   *   A pair of view storage entity and executable.
   */
  protected function setupValidMocks($use_ajax = self::USE_AJAX) {
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
    $executable->expects($this->any())
      ->method('setDisplay')
      ->willReturn(TRUE);
    $executable->expects($this->atMost(1))
      ->method('preview')
      ->will($this->returnValue(['#markup' => 'View result']));

    $this->executableFactory->expects($this->once())
      ->method('get')
      ->with($view)
      ->will($this->returnValue($executable));

    $display_handler = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    // Ensure that the pager element is not set.
    $display_handler->expects($this->never())
      ->method('setOption');
    $display_handler->expects($this->any())
      ->method('ajaxEnabled')
      ->willReturn($use_ajax);

    $display_collection = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();
    $display_collection->expects($this->any())
      ->method('get')
      ->with('page_1')
      ->will($this->returnValue($display_handler));

    $executable->display_handler = $display_handler;
    $executable->displayHandlers = $display_collection;

    return [$view, $executable];
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
